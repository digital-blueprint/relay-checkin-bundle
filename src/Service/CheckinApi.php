<?php

declare(strict_types=1);
/**
 * Checkin API service.
 */

namespace Dbp\Relay\CheckinBundle\Service;

use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Entity\CheckOutAction;
use Dbp\Relay\CheckinBundle\Entity\GuestCheckInAction;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotLoadedException;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Helpers\Tools;
use Dbp\Relay\CheckinBundle\Message\GuestCheckOutMessage;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Options;
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

    public const EMAIL_LOCAL_DATA_ATTRIBUTE = 'email';
    // Caching time of https://campusqr-dev.tugraz.at/location/list and the config
    public const LOCATION_CACHE_TTL = 300;
    public const CONFIG_KEY_AUTO_CHECK_OUT_MINUTES = 'autoCheckOutMinutes';

    private ?object $clientHandler = null;
    private ?CacheItemPoolInterface $cachePool = null;
    private CheckinUrlApi $urls;
    private string $campusQRUrl = '';
    private string $campusQRToken = '';
    private int $autoCheckOutMinutes = 0;

    /**
     * CheckinApi constructor.
     */
    public function __construct(
        private readonly PersonProviderInterface $personProvider,
        private readonly MessageBusInterface $bus,
        private readonly LockFactory $lockFactory)
    {
        $this->urls = new CheckinUrlApi();
    }

    public function setConfig(array $config): void
    {
        $this->campusQRUrl = $config['campus_qr_url'] ?? '';
        $this->campusQRToken = $config['campus_qr_token'] ?? '';
    }

    public function setCache(?CacheItemPoolInterface $cachePool): void
    {
        $this->cachePool = $cachePool;
    }

    /**
     * Replace the guzzle client handler for testing.
     */
    public function setClientHandler(?object $handler): void
    {
        $this->clientHandler = $handler;
    }

    private function getClient(bool $withCache = false): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
            'headers' => ['X-Authorization' => $this->campusQRToken],
        ];

        if ($this->logger !== null) {
            $stack->push(Tools::createLoggerMiddleware($this->logger));
        }

        if ($withCache && $this->cachePool !== null) {
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
     * Check if CampusQR is reachable.
     */
    public function checkConnection(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->campusQRUrl);
    }

    /**
     * Check if we can talk to the API.
     */
    public function checkApi(): void
    {
        $client = $this->getClient();
        $url = $this->urls->getLocationListRequestUrl($this->campusQRUrl);
        $client->request('GET', $url);
    }

    /**
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
            'body' => json_encode(['email' => $person->getLocalDataValue(CheckinApi::EMAIL_LOCAL_DATA_ATTRIBUTE)]),
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
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('CheckInAction could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function sendCampusQRGuestCheckInRequest(GuestCheckInAction $locationGuestCheckInAction): bool
    {
        $location = $locationGuestCheckInAction->getLocation();
        $seatNumber = $locationGuestCheckInAction->getSeatNumber();
        $email = $locationGuestCheckInAction->getEmail();
        $currentPerson = $this->getCurrentPerson();

        $client = $this->getClient();
        $options = [
            'body' => json_encode(['email' => $email, 'host' => $currentPerson->getLocalDataValue(self::EMAIL_LOCAL_DATA_ATTRIBUTE)]),
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
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('GuestCheckInAction could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function sendCampusQRCheckOutRequest(string $email, Place $location, ?int $seatNumber): bool
    {
        $client = $this->getClient();
        $options = [
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
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('Check out was not be possible: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function sendCampusQRCheckOutRequestForCheckOutAction(CheckOutAction $locationCheckOutAction): bool
    {
        $person = $locationCheckOutAction->getAgent();
        $currentPerson = $this->getCurrentPerson();

        if ($person->getIdentifier() !== $currentPerson->getIdentifier()) {
            throw new AccessDeniedHttpException('You are not allowed to check-out this check-in!');
        }

        $location = $locationCheckOutAction->getLocation();
        $seatNumber = $locationCheckOutAction->getSeatNumber();

        return $this->sendCampusQRCheckOutRequest($person->getLocalDataValue(self::EMAIL_LOCAL_DATA_ATTRIBUTE), $location, $seatNumber);
    }

    /**
     * Fetches all places or searches for all name parts in $name.
     *
     * @param string $name
     *
     * @return Place[]
     *
     * @throws ItemNotLoadedException
     */
    public function fetchPlaces($name = ''): array
    {
        $collection = [];

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

            $collection[] = $checkInPlace;
        }

        return $collection;
    }

    /**
     * @throws ItemNotLoadedException
     * @throws NotFoundHttpException
     */
    public function fetchPlace(string $id): ?Place
    {
        $checkInPlaces = $this->fetchPlaces();

        foreach ($checkInPlaces as $checkInPlace) {
            if ($checkInPlace->getIdentifier() === $id) {
                return $checkInPlace;
            }
        }

        return null;
    }

    public function fetchPlacesJsonData(): array
    {
        $client = $this->getClient(true);

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/list
            $url = $this->urls->getLocationListRequestUrl($this->campusQRUrl);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch places!');
            }

            throw new ItemNotLoadedException(sprintf('Places could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotLoadedException(sprintf('Places could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param mixed $jsonData
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
     * @throws ApiError
     */
    public function getCurrentPerson(): ?Person
    {
        $options = [];
        Options::requestLocalDataAttributes($options, ['email']);

        return $this->personProvider->getCurrentPerson($options);
    }

    /**
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
     * @param string $location
     * @param ?int   $seatNumber
     *
     * @return CheckInAction[]
     *
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInActionsOfEmail(string $email, $location = '', $seatNumber = null): array
    {
        $collection = [];

        $authenticDocumentTypesJsonData = $this->fetchCheckInActionsOfEMailJsonData($email);

        foreach ($authenticDocumentTypesJsonData as $jsonData) {
            // Search for a location and seat if they were set
            // Search for the location alone if no seat was set
            if (($location !== '' && $jsonData['locationId'] === $location
                && $seatNumber !== null && $jsonData['seat'] === $seatNumber)
                || ($location !== '' && $jsonData['locationId'] === $location && $seatNumber === null)
                || ($location === '' && $seatNumber === null)) {
                $checkInAction = $this->locationCheckInActionFromJsonItem($jsonData);
                $checkInAction->setEndTime($this->fetchMaxCheckinEndTime($checkInAction->getStartTime()));
                $collection[] = $checkInAction;
            }
        }

        return $collection;
    }

    /**
     * @param string $location
     * @param ?int   $seatNumber
     *
     * @return CheckInAction[]
     *
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInActionsOfCurrentPerson($location = '', $seatNumber = null): array
    {
        $person = $this->getCurrentPerson();

        return $this->fetchCheckInActionsOfEmail($person->getLocalDataValue(self::EMAIL_LOCAL_DATA_ATTRIBUTE), $location, $seatNumber);
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInActionsOfEMailJsonData(string $email): array
    {
        $client = $this->getClient();

        $options = [
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
        } catch (\Exception|UriException $e) {
            throw new ItemNotLoadedException(sprintf('CheckInActions could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param mixed $jsonData
     *
     * @throws ItemNotLoadedException
     */
    public function locationCheckInActionFromJsonItem($jsonData, ?PersonProviderInterface $person = null): CheckInAction
    {
        if ($person === null) {
            $person = $this->getCurrentPerson();
        }

        // We don't get any maximumPhysicalAttendeeCapacity, so we are hiding it in the result when fetching the
        // CheckInAction list via normalization context group "Checkin:outputList"
        $checkInPlace = new Place();
        $checkInPlace->setIdentifier($jsonData['locationId']);
        $checkInPlace->setName($jsonData['locationName']);

        // The api returns the "checkInDate" as float, like 1.60214467586E12, see https://github.com/studo-app/campus-qr/issues/53
        $dateTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateTime = $dateTime->setTimestamp((int) ($jsonData['checkInDate'] / 1000));

        $locationCheckInAction = new CheckInAction();
        $locationCheckInAction->setIdentifier($jsonData['id']);
        $locationCheckInAction->setSeatNumber($jsonData['seat']);
        $locationCheckInAction->setStartTime($dateTime);
        $locationCheckInAction->setAgent($person);
        $locationCheckInAction->setLocation($checkInPlace);

        return $locationCheckInAction;
    }

    public function setCampusQRUrl(string $campusQRUrl): CheckinApi
    {
        $this->campusQRUrl = $campusQRUrl;

        return $this;
    }

    public function setCampusQRToken(string $campusQRToken): CheckinApi
    {
        $this->campusQRToken = $campusQRToken;

        return $this;
    }

    /**
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
     * @return mixed
     *
     * @throws ItemNotLoadedException
     * @throws AccessDeniedHttpException
     */
    public function fetchConfig(string $configKey)
    {
        $client = $this->getClient(true);

        try {
            $url = $this->urls->getConfigUrl($this->campusQRUrl, $configKey);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status === 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch config!');
            }

            throw new ItemNotLoadedException(sprintf('Config could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotLoadedException(sprintf('Config could not be loaded: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function fetchMaxCheckinEndTime(?\DateTimeInterface $date = null): \DateTimeInterface
    {
        $startDate = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($date !== null) {
            $startDate = $startDate->setTimestamp($date->getTimestamp());
        }

        // only fetch if not already fetched
        if ($this->autoCheckOutMinutes === 0) {
            $this->autoCheckOutMinutes = (int) $this->fetchConfig(self::CONFIG_KEY_AUTO_CHECK_OUT_MINUTES);
        }

        $autoCheckOutMinutes = $this->autoCheckOutMinutes;

        $newData = $startDate->add(new \DateInterval("PT{$autoCheckOutMinutes}M"));

        return $newData;
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
     * Create a lock for a specific location for an email address.
     * Can be used to serialize non-atomic updates during a check-in/out action.
     */
    public function createLock(string $email, string $location, ?int $seatNumber): LockInterface
    {
        $resourceKey = sprintf(
            'checkin-%s-%s-%s',
            $location,
            $seatNumber,
            $email
        );

        return $this->lockFactory->createLock($resourceKey, 60, true);
    }
}
