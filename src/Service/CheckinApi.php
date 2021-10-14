<?php

declare(strict_types=1);
/**
 * Checkin API service.
 */

namespace Dbp\Relay\CheckinBundle\Service;

use Dbp\Relay\BaseBundle\API\PersonProviderInterface;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Entity\CheckOutAction;
use Dbp\Relay\CheckinBundle\Entity\GuestCheckInAction;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotLoadedException;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotUsableException;
use Dbp\Relay\CheckinBundle\Helpers\Tools;
use Dbp\Relay\CheckinBundle\Message\GuestCheckOutMessage;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use League\Uri\Contracts\UriException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class CheckinApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $clientHandler;

    private $cachePool;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    /**
     * @var CheckinUrlApi
     */
    private $urls;

    /**
     * @var string
     */
    private $campusQRUrl = '';

    /**
     * @var string
     */
    private $campusQRToken = '';

    /**
     * @var int
     */
    private $autoCheckOutMinutes = 0;

    // Caching time of https://campusqr-dev.tugraz.at/location/list and the config
    public const LOCATION_CACHE_TTL = 300;

    public const CONFIG_KEY_AUTO_CHECK_OUT_MINUTES = 'autoCheckOutMinutes';

    /**
     * CheckinApi constructor.
     *
     * @param PersonProviderInterface $personProvider
     * @param MessageBusInterface $bus
     * @param LockFactory $lockFactory
     */
    public function __construct(
        PersonProviderInterface $personProvider,
        MessageBusInterface $bus,
        LockFactory $lockFactory
    ) {
        $this->clientHandler = null;
        $this->personProvider = $personProvider;
        $this->bus = $bus;
        $this->lockFactory = $lockFactory;
        $this->urls = new CheckinUrlApi();

        $this->campusQRUrl = '';
        $this->campusQRToken = '';
    }

    public function setConfig(array $config)
    {
        $this->campusQRUrl = $config['campus_qr_url'] ?? '';
        $this->campusQRToken = $config['campus_qr_token'] ?? '';
    }

    public function setCache(?CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * Replace the guzzle client handler for testing.
     *
     * @param object|null $handler
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
        ];

        if ($this->logger !== null) {
            $stack->push(Tools::createLoggerMiddleware($this->logger));
        }

        return new Client($client_options);
    }

    private function getLocationClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
        ];

        if ($this->logger !== null) {
            $stack->push(Tools::createLoggerMiddleware($this->logger));
        }

        if ($this->cachePool !== null) {
            $cacheMiddleWare = new CacheMiddleware(
                new GreedyCacheStrategy(
                    new Psr6CacheStorage($this->cachePool),
                    self::LOCATION_CACHE_TTL
                )
            );
            $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
            $stack->push($cacheMiddleWare);
        }

        return new Client($client_options);
    }

    /**
     * @param CheckInAction $locationCheckInAction
     *
     * @return bool
     *
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function sendCampusQRCheckInRequest(CheckInAction $locationCheckInAction): bool
    {
        $location = $locationCheckInAction->getLocation();
        $seatNumber = $locationCheckInAction->getSeatNumber();
        $person = $locationCheckInAction->getAgent();

        $client = $this->getClient();
        $options = [
            'body' => json_encode(['email' => $person->getEmail()]),
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/f0ad66aaaf1debabb44a/visit
            $url = $this->urls->getCheckInRequestUrl($this->campusQRUrl, $location->getIdentifier(), $seatNumber);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            $body = $response->getBody()->getContents();

            return $body === 'ok';
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('You are not allowed to check-in at this location!');
            }

            throw new ItemNotStoredException(sprintf('CheckInAction could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception | UriException $e) {
            throw new ItemNotStoredException(sprintf('CheckInAction could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param GuestCheckInAction $locationGuestCheckInAction
     *
     * @return bool
     *
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function sendCampusQRGuestCheckInRequest(GuestCheckInAction $locationGuestCheckInAction): bool
    {
        $location = $locationGuestCheckInAction->getLocation();
        $seatNumber = $locationGuestCheckInAction->getSeatNumber();
        $email = $locationGuestCheckInAction->getEmail();
        $currentPerson = $this->personProvider->getCurrentPerson();

        $client = $this->getClient();
        $options = [
            'headers' => ['X-Authorization' => $this->campusQRToken],
            'body' => json_encode(['email' => $email, 'host' => $currentPerson->getEmail()]),
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/f0ad66aaaf1debabb44a/visit
            $url = $this->urls->getGuestCheckInRequestUrl(
                $this->campusQRUrl,
                $location->getIdentifier(),
                $seatNumber
            );

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            $body = $response->getBody()->getContents();

            return $body === 'ok';
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('You are not allowed to check-in at this location!');
            }

            throw new ItemNotStoredException(sprintf('GuestCheckInAction could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception | UriException $e) {
            throw new ItemNotStoredException(sprintf('GuestCheckInAction could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param string $email
     * @param Place $location
     * @param int|null $seatNumber
     *
     * @return bool
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function sendCampusQRCheckOutRequest(string $email, Place $location, ?int $seatNumber): bool
    {
        $client = $this->getClient();
        $options = [
            'headers' => ['X-Authorization' => $this->campusQRToken],
            'body' => json_encode(['email' => $email]),
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/f0ad66aaaf1debabb44a/visit
            $url = $this->urls->getCheckOutRequestUrl($this->campusQRUrl, $location->getIdentifier(), $seatNumber);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            $body = $response->getBody()->getContents();

            return $body === 'ok';
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('You are not allowed to check-out at this location!');
            }

            throw new ItemNotStoredException(sprintf('Check out was not be possible: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception | UriException $e) {
            throw new ItemNotStoredException(sprintf('Check out was not be possible: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param CheckOutAction $locationCheckOutAction
     *
     * @return bool
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function sendCampusQRCheckOutRequestForCheckOutAction(CheckOutAction $locationCheckOutAction): bool
    {
        $person = $locationCheckOutAction->getAgent();
        $currentPerson = $this->personProvider->getCurrentPerson();

        if ($person->getIdentifier() !== $currentPerson->getIdentifier()) {
            throw new AccessDeniedHttpException('You are not allowed to check-out this check-in!');
        }

        $location = $locationCheckOutAction->getLocation();
        $seatNumber = $locationCheckOutAction->getSeatNumber();

        return $this->sendCampusQRCheckOutRequest($person->getEmail(), $location, $seatNumber);
    }

    /**
     * Fetches all places or searches for all name parts in $name.
     *
     * @param string $name
     *
     * @return ArrayCollection|Place[]
     *
     * @throws ItemNotLoadedException
     */
    public function fetchPlaces($name = ''): ArrayCollection
    {
        /** @var ArrayCollection<int,Place> $collection */
        $collection = new ArrayCollection();

        $authenticDocumentTypesJsonData = $this->fetchPlacesJsonData();
        $name = trim($name);
        $nameParts = explode(' ', $name);
        $hasName = $name !== '';

        foreach ($authenticDocumentTypesJsonData as $jsonData) {
            $checkInPlace = $this->checkInPlaceFromJsonItem($jsonData);

            // search for name parts if a name was set
            if ($hasName) {
                foreach ($nameParts as $namePart) {
                    // skip as soon a name part wasn't found
                    if (stripos($checkInPlace->getName(), $namePart) === false) {
                        continue 2;
                    }
                }
            }

            $collection->add($checkInPlace);
        }

        return $collection;
    }

    /**
     * @param string $id
     *
     * @return Place
     *
     * @throws ItemNotLoadedException
     * @throws NotFoundHttpException
     */
    public function fetchPlace(string $id): Place
    {
        $checkInPlaces = $this->fetchPlaces();

        foreach ($checkInPlaces as $checkInPlace) {
            if ($checkInPlace->getIdentifier() === $id) {
                return $checkInPlace;
            }
        }

        throw new NotFoundHttpException('Location was not found!');
    }

    public function fetchPlacesJsonData(): array
    {
        $client = $this->getLocationClient();

        $options = [
            'headers' => ['X-Authorization' => $this->campusQRToken],
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/list
            $url = $this->urls->getLocationListRequestUrl($this->campusQRUrl);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch places!');
            }

            throw new ItemNotLoadedException(sprintf('Places could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception | UriException $e) {
            throw new ItemNotLoadedException(sprintf('Places could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param mixed $jsonData
     *
     * @return Place
     */
    public function checkInPlaceFromJsonItem($jsonData): Place
    {
        $checkInPlace = new Place();
        $checkInPlace->setIdentifier($jsonData['id']);
        $checkInPlace->setName($jsonData['name']);

        if ($jsonData['seatCount'] !== null) {
            $checkInPlace->setMaximumPhysicalAttendeeCapacity($jsonData['seatCount']);
        }

        return $checkInPlace;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return mixed
     *
     * @throws ItemNotLoadedException
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ItemNotLoadedException(sprintf('Invalid json: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param string $email
     * @param string $location
     * @param ?int $seatNumber
     *
     * @return ArrayCollection
     *
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInActionsOfEmail(string $email, $location = '', $seatNumber = null): ArrayCollection
    {
        /** @var ArrayCollection<int,CheckInAction> $collection */
        $collection = new ArrayCollection();

        $authenticDocumentTypesJsonData = $this->fetchCheckInActionsOfEMailJsonData($email);

        foreach ($authenticDocumentTypesJsonData as $jsonData) {
            // Search for a location and seat if they were set
            // Search for the location alone if no seat was set
            if (($location !== '' && $jsonData['locationId'] === $location &&
                $seatNumber !== null && $jsonData['seat'] === $seatNumber) ||
                ($location !== '' && $jsonData['locationId'] === $location && $seatNumber === null) ||
                ($location === '' && $seatNumber === null)) {
                $checkInAction = $this->locationCheckInActionFromJsonItem($jsonData);
                $checkInAction->setEndTime($this->fetchMaxCheckinEndTime($checkInAction->getStartTime()));
                $collection->add($checkInAction);
            }
        }

        return $collection;
    }

    /**
     * @param string $location
     * @param ?int $seatNumber
     *
     * @return ArrayCollection
     *
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInActionsOfCurrentPerson($location = '', $seatNumber = null): ArrayCollection
    {
        $person = $this->personProvider->getCurrentPerson();

        return $this->fetchCheckInActionsOfEmail($person->getEmail(), $location, $seatNumber);
    }

    /**
     * @param string $email
     *
     * @return array
     *
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInActionsOfEMailJsonData(string $email): array
    {
        $client = $this->getClient();

        $options = [
            'headers' => ['X-Authorization' => $this->campusQRToken],
            'body' => json_encode(['emailAddress' => $email]),
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/list
            $url = $this->urls->getCheckInActionListOfCurrentPersonRequestUrl($this->campusQRUrl);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch CheckInActions!');
            }

            throw new ItemNotLoadedException(sprintf('CheckInActions could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception | UriException $e) {
            throw new ItemNotLoadedException(sprintf('CheckInActions could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param mixed $jsonData
     * @param PersonProviderInterface|null $person
     *
     * @return CheckInAction
     *
     * @throws ItemNotLoadedException
     */
    public function locationCheckInActionFromJsonItem($jsonData, ?PersonProviderInterface $person = null): CheckInAction
    {
        if ($person === null) {
            $person = $this->personProvider->getCurrentPerson();
        }

        // We don't get any maximumPhysicalAttendeeCapacity, so we are hiding it in the result when fetching the
        // CheckInAction list via normalization context group "Checkin:outputList"
        $checkInPlace = new Place();
        $checkInPlace->setIdentifier($jsonData['locationId']);
        $checkInPlace->setName($jsonData['locationName']);

        // The api returns the "checkInDate" as float, like 1.60214467586E12, see https://github.com/studo-app/campus-qr/issues/53
        $dateTime = new \DateTime();
        $dateTime->setTimestamp((int) ($jsonData['checkInDate'] / 1000));

        $locationCheckInAction = new CheckInAction();
        $locationCheckInAction->setIdentifier($jsonData['id']);
        $locationCheckInAction->setSeatNumber($jsonData['seat']);
        $locationCheckInAction->setStartTime($dateTime);
        $locationCheckInAction->setAgent($person);
        $locationCheckInAction->setLocation($checkInPlace);

        return $locationCheckInAction;
    }

    /**
     * @param string $campusQRUrl
     *
     * @return CheckinApi
     */
    public function setCampusQRUrl(string $campusQRUrl): CheckinApi
    {
        $this->campusQRUrl = $campusQRUrl;

        return $this;
    }

    /**
     * @param string $campusQRToken
     *
     * @return CheckinApi
     */
    public function setCampusQRToken(string $campusQRToken): CheckinApi
    {
        $this->campusQRToken = $campusQRToken;

        return $this;
    }

    /**
     * @param \Dbp\Relay\CheckinBundle\Entity\Place $location
     * @param int|null $seatNumber
     *
     * @throws ItemNotStoredException
     */
    public function seatCheck(Place $location, ?int $seatNumber): void
    {
        $maximumPhysicalAttendeeCapacity = $location->getMaximumPhysicalAttendeeCapacity();

        if ($seatNumber === null && $maximumPhysicalAttendeeCapacity !== null) {
            throw new ItemNotStoredException('Location has seats activated, you need to set a seatNumber!');
        } elseif ($seatNumber !== null && $maximumPhysicalAttendeeCapacity === null) {
            throw new ItemNotStoredException("Location doesn't have any seats activated, you cannot set a seatNumber!");
        } elseif ($seatNumber !== null && $seatNumber > $maximumPhysicalAttendeeCapacity) {
            throw new ItemNotStoredException('seatNumber must not exceed maximumPhysicalAttendeeCapacity of location!');
        } elseif ($seatNumber !== null && $seatNumber < 1) {
            throw new ItemNotStoredException('seatNumber too low!');
        }
    }

    /**
     * @param string $configKey
     *
     * @return mixed
     *
     * @throws ItemNotLoadedException
     * @throws AccessDeniedHttpException
     */
    public function fetchConfig(string $configKey)
    {
        $client = $this->getLocationClient();

        $options = [
            'headers' => ['X-Authorization' => $this->campusQRToken],
        ];

        try {
            $url = $this->urls->getConfigUrl($this->campusQRUrl, $configKey);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch config!');
            }

            throw new ItemNotLoadedException(sprintf('Config could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception | UriException $e) {
            throw new ItemNotLoadedException(sprintf('Config could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param \DateTime|null $date
     *
     * @return \DateTime
     *
     * @throws ItemNotLoadedException
     */
    public function fetchMaxCheckinEndTime(\DateTime $date = null): \DateTime
    {
        if ($date === null) {
            $date = new \DateTime();
        }

        // only fetch if not already fetched
        if ($this->autoCheckOutMinutes === 0) {
            $this->autoCheckOutMinutes = (int) $this->fetchConfig(self::CONFIG_KEY_AUTO_CHECK_OUT_MINUTES);
        }

        $autoCheckOutMinutes = $this->autoCheckOutMinutes;

        // needs a clone or else $date will be modified outside the function!
        $newDate = clone $date;
        $newDate->add(new \DateInterval("PT${autoCheckOutMinutes}M"));

        return $newDate;
    }

    public function createAndDispatchGuestCheckOutMessage(GuestCheckInAction $locationGuestCheckInAction)
    {
        $message = new GuestCheckOutMessage(
            $locationGuestCheckInAction->getEmail(),
            $locationGuestCheckInAction->getLocation(),
            $locationGuestCheckInAction->getSeatNumber()
        );

        $this->bus->dispatch(
            $message, [
            $this->getDelayStampFromGuestCheckInAction($locationGuestCheckInAction),
        ]);

        return $message;
    }

    /**
     * @param GuestCheckInAction $locationGuestCheckInAction
     *
     * @return DelayStamp
     */
    public function getDelayStampFromGuestCheckInAction(GuestCheckInAction $locationGuestCheckInAction): DelayStamp
    {
        $endTime = $locationGuestCheckInAction->getEndTime();
        $seconds = $endTime->getTimestamp() - time();

        if ($seconds < 0) {
            $seconds = 0;
        }

        return new DelayStamp($seconds * 1000);
    }

    /**
     * Handles the delayed checkout of guests.
     *
     * @param GuestCheckOutMessage $message
     */
    public function handleGuestCheckOutMessage(GuestCheckOutMessage $message)
    {
        try {
            $this->sendCampusQRCheckOutRequest(
                $message->getEmail(),
                $message->getLocation(),
                $message->getSeatNumber()
            );
        } catch (ItemNotLoadedException $e) {
        } catch (ItemNotStoredException $e) {
        }
    }

    /**
     * @param string $resource
     * @param int $maxRetry retry this amount of times ever 100ms
     *
     * @return LockInterface
     *
     * @throws ItemNotUsableException
     */
    public function acquireBlockingLock(string $resource, $maxRetry = 300): LockInterface
    {
        $resourceKey = 'check-in-'.$resource;
        $lock = $this->lockFactory->createLock($resourceKey);
        $counter = 0;

        do {
            // Redis can't have blocking locks, so we will simulate it
            $gotLock = $lock->acquire();

            if (!$gotLock) {
                usleep(100000);
            }
        } while (!$gotLock && (++$counter <= $maxRetry));

        if (!$gotLock) {
            throw new ItemNotUsableException(sprintf('System was not able to get lock on ressource: %s', $resource));
        }

        return $lock;
    }
}
