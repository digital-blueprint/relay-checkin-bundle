<?php

declare(strict_types=1);
/**
 * LocationCheckIn API service.
 */

namespace DBP\API\LocationCheckInBundle\Service;

use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Helpers\GuzzleTools;
use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\CoreBundle\Service\DBPLogger;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use DBP\API\LocationCheckInBundle\Entity\LocationGuestCheckInAction;
use DBP\API\LocationCheckInBundle\Message\LocationGuestCheckOutMessage;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class LocationCheckInApi
{
    private $clientHandler;

    private $logger;

    private $container;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    /**
     * @var LocationCheckInUrlApi
     */
    private $urls;

    /**
     * @var string
     */
    private $campusQRUrl = "";

    /**
     * @var string
     */
    private $campusQRToken = "";

    /**
     * @var int
     */
    private $autoCheckOutMinutes = 0;

    // Caching time of https://campusqr-dev.tugraz.at/location/list
    const LOCATION_CACHE_TTL = 300;

    const CONFIG_KEY_AUTO_CHECK_OUT_MINUTES = "autoCheckOutMinutes";


    /**
     * LocationCheckInApi constructor.
     * @param DBPLogger $logger
     * @param PersonProviderInterface $personProvider
     * @param ContainerInterface $container
     * @param MessageBusInterface $bus
     */
    public function __construct(
        DBPLogger $logger,
        PersonProviderInterface $personProvider,
        ContainerInterface $container,
        MessageBusInterface $bus
    )
    {
        $this->clientHandler = null;
        $this->logger = $logger;
        $this->personProvider = $personProvider;
        $this->container = $container;
        $this->bus = $bus;
        $this->urls = new LocationCheckInUrlApi();

        $config = $container->getParameter('dbp_api.location_check_in.config');
        $this->campusQRUrl = $config['campus_qr_url'] ?? '';
        $this->campusQRToken = $config['campus_qr_token'] ?? '';
    }

    /**
     * Replace the guzzle client handler for testing.
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

        $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));

        return new Client($client_options);
    }

    private function getLocationClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
        ];

        $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));

        $guzzleCachePool = $this->getCachePool();
        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($guzzleCachePool),
                self::LOCATION_CACHE_TTL
            )
        );

        $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
        $stack->push($cacheMiddleWare);

        return new Client($client_options);
    }

    private function getCachePool(): CacheItemPoolInterface
    {
        $guzzleCachePool = $this->container->get('dbp_api.cache.location_check_in.location');
        assert($guzzleCachePool instanceof CacheItemPoolInterface);

        return $guzzleCachePool;
    }

    /**
     * @param LocationCheckInAction $locationCheckInAction
     * @return bool
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function sendCampusQRCheckInRequest(LocationCheckInAction $locationCheckInAction): bool {
        $location = $locationCheckInAction->getLocation();
        $seatNumber = $locationCheckInAction->getSeatNumber();
        $person = $locationCheckInAction->getAgent();

        $client = $this->getClient();
        $options = [
            'body' => json_encode(['email' => $person->getEmail()])
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/f0ad66aaaf1debabb44a/visit
            $url = $this->urls->getCheckInRequestUrl($this->campusQRUrl, $location->getIdentifier(), $seatNumber);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            $body = $response->getBody()->getContents();

            return $body === "ok";
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                throw new AccessDeniedHttpException('You are not allowed to check-in at this location!');
            }

            throw new ItemNotStoredException(sprintf('LocationCheckInAction could not be stored: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('LocationCheckInAction could not be stored: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param LocationGuestCheckInAction $locationGuestCheckInAction
     * @return bool
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function sendCampusQRGuestCheckInRequest(LocationGuestCheckInAction $locationGuestCheckInAction): bool {
        $location = $locationGuestCheckInAction->getLocation();
        $seatNumber = $locationGuestCheckInAction->getSeatNumber();
        $email = $locationGuestCheckInAction->getEmail();
        $currentPerson = $this->personProvider->getCurrentPerson();

        $client = $this->getClient();
        $options = [
            'headers' => [ 'X-Authorization' => $this->campusQRToken ],
            'body' => json_encode(['email' => $email, 'host' => $currentPerson->getEmail()])
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

            return $body === "ok";
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                throw new AccessDeniedHttpException('You are not allowed to check-in at this location!');
            }

            throw new ItemNotStoredException(sprintf('LocationGuestCheckInAction could not be stored: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('LocationGuestCheckInAction could not be stored: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param string $email
     * @param CheckInPlace $location
     * @param int|null $seatNumber
     * @return bool
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function sendCampusQRCheckOutRequest(string $email, CheckInPlace $location, ?int $seatNumber): bool {
        $client = $this->getClient();
        $options = [
            'headers' => [ 'X-Authorization' => $this->campusQRToken ],
            'body' => json_encode(['email' => $email])
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/f0ad66aaaf1debabb44a/visit
            $url = $this->urls->getCheckOutRequestUrl($this->campusQRUrl, $location->getIdentifier(), $seatNumber);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            $body = $response->getBody()->getContents();

            return $body === "ok";
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                throw new AccessDeniedHttpException('You are not allowed to check-out at this location!');
            }

            throw new ItemNotStoredException(sprintf('Check out was not be possible: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('Check out was not be possible: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param LocationCheckOutAction $locationCheckOutAction
     * @return bool
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function sendCampusQRCheckOutRequestForLocationCheckOutAction(LocationCheckOutAction $locationCheckOutAction): bool {
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
     * Fetches all places or searches for all name parts in $name
     *
     * @param string $name
     * @return ArrayCollection|CheckInPlace[]
     * @throws ItemNotLoadedException
     */
    public function fetchCheckInPlaces($name = ""): ArrayCollection
    {
        /** @var ArrayCollection<int,CheckInPlace> $collection */
        $collection = new ArrayCollection();

        $authenticDocumentTypesJsonData = $this->fetchCheckInPlacesJsonData();
        $name = trim($name);
        $nameParts = explode(" ", $name);
        $hasName = $name !== "";

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
     * @return CheckInPlace
     * @throws ItemNotLoadedException
     * @throws NotFoundHttpException
     */
    public function fetchCheckInPlace(string $id): CheckInPlace {
        $checkInPlaces = $this->fetchCheckInPlaces();

        foreach($checkInPlaces as $checkInPlace) {
            if ($checkInPlace->getIdentifier() === $id) {
                return $checkInPlace;
            }
        }

        throw new NotFoundHttpException('Location was not found!');
    }

    public function fetchCheckInPlacesJsonData(): array {
        $client = $this->getLocationClient();

        $options = [
            'headers' => [ 'X-Authorization' => $this->campusQRToken ]
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/list
            $url = $this->urls->getLocationListRequestUrl($this->campusQRUrl);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch places!');
            }

            throw new ItemNotLoadedException(sprintf('Places could not be loaded: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotLoadedException(sprintf('Places could not be loaded: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param $jsonData
     * @return CheckInPlace
     */
    public function checkInPlaceFromJsonItem($jsonData): CheckInPlace {
        $checkInPlace = new CheckInPlace();
        $checkInPlace->setIdentifier($jsonData["id"]);
        $checkInPlace->setName($jsonData["name"]);

        if ($jsonData["seatCount"] !== null) {
            $checkInPlace->setMaximumPhysicalAttendeeCapacity($jsonData["seatCount"]);
        }

        return $checkInPlace;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     *
     * @throws ItemNotLoadedException
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return CoreTools::decodeJSON((string) $body, true);
        } catch (JsonException $e) {
            throw new ItemNotLoadedException(sprintf('Invalid json: %s', CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param string $email
     * @param string $location
     * @param ?int $seatNumber
     * @return ArrayCollection
     * @throws ItemNotLoadedException
     */
    public function fetchLocationCheckInActionsOfEmail(string $email, $location = "", $seatNumber = null): ArrayCollection {
        /** @var ArrayCollection<int,LocationCheckInAction> $collection */
        $collection = new ArrayCollection();

        $authenticDocumentTypesJsonData = $this->fetchLocationCheckInActionsOfEMailJsonData($email);

        foreach ($authenticDocumentTypesJsonData as $jsonData)
        {
            // Search for a location and seat if they were set
            // Search for the location alone if no seat was set
            if (($location !== "" && $jsonData["locationId"] === $location &&
                $seatNumber !== null && $jsonData["seat"] === $seatNumber) ||
                ($location !== "" && $jsonData["locationId"] === $location && $seatNumber === null) ||
                ($location === "" && $seatNumber === null))
            {
                $checkInAction = $this->locationCheckInActionFromJsonItem($jsonData);
                $checkInAction->setEndTime($this->fetchMaxCheckInEndTime($checkInAction->getStartTime()));
                $collection->add($checkInAction);
            }
        }

        return $collection;
    }

    /**
     * @param string $location
     * @param ?int $seatNumber
     * @return ArrayCollection
     * @throws ItemNotLoadedException
     */
    public function fetchLocationCheckInActionsOfCurrentPerson($location = "", $seatNumber = null): ArrayCollection {
        $person = $this->personProvider->getCurrentPerson();

        return $this->fetchLocationCheckInActionsOfEmail($person->getEmail(), $location, $seatNumber);
    }

    /**
     * @param string $email
     * @return array
     * @throws ItemNotLoadedException
     */
    public function fetchLocationCheckInActionsOfEMailJsonData(string $email): array {
        $client = $this->getClient();

        $options = [
            'headers' => [ 'X-Authorization' => $this->campusQRToken ],
            'body' => json_encode(['emailAddress' => $email])
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/list
            $url = $this->urls->getLocationCheckInActionListOfCurrentPersonRequestUrl($this->campusQRUrl);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch LocationCheckInActions!');
            }

            throw new ItemNotLoadedException(sprintf('LocationCheckInActions could not be loaded: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotLoadedException(sprintf('LocationCheckInActions could not be loaded: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param $jsonData
     * @param PersonProviderInterface|null $person
     * @return LocationCheckInAction
     * @throws ItemNotLoadedException
     */
    public function locationCheckInActionFromJsonItem($jsonData, ?PersonProviderInterface $person = null): LocationCheckInAction {
        if ($person === null) {
            $person = $this->personProvider->getCurrentPerson();
        }

        // We don't get any maximumPhysicalAttendeeCapacity, so we are hiding it in the result when fetching the
        // LocationCheckInAction list via normalization context group "LocationCheckIn:outputList"
        $checkInPlace = new CheckInPlace();
        $checkInPlace->setIdentifier($jsonData["locationId"]);
        $checkInPlace->setName($jsonData["locationName"]);

        // The api returns the "checkInDate" as float, like 1.60214467586E12, see https://github.com/studo-app/campus-qr/issues/53
        $dateTime = new \DateTime();
        $dateTime->setTimestamp((int) ($jsonData["checkInDate"] / 1000));

        $locationCheckInAction = new LocationCheckInAction();
        $locationCheckInAction->setIdentifier($jsonData["id"]);
        $locationCheckInAction->setSeatNumber($jsonData["seat"]);
        $locationCheckInAction->setStartTime($dateTime);
        $locationCheckInAction->setAgent($person);
        $locationCheckInAction->setLocation($checkInPlace);

        return $locationCheckInAction;
    }

    /**
     * @param string $campusQRUrl
     * @return LocationCheckInApi
     */
    public function setCampusQRUrl(string $campusQRUrl): LocationCheckInApi
    {
        $this->campusQRUrl = $campusQRUrl;

        return $this;
    }

    /**
     * @param string $campusQRToken
     * @return LocationCheckInApi
     */
    public function setCampusQRToken(string $campusQRToken): LocationCheckInApi
    {
        $this->campusQRToken = $campusQRToken;

        return $this;
    }

    /**
     * @param \DBP\API\LocationCheckInBundle\Entity\CheckInPlace $location
     * @param int|null $seatNumber
     * @throws ItemNotStoredException
     */
    public function seatCheck(CheckInPlace $location, ?int $seatNumber): void
    {
        $maximumPhysicalAttendeeCapacity = $location->getMaximumPhysicalAttendeeCapacity();

        if ($seatNumber === null && $maximumPhysicalAttendeeCapacity !== null) {
            throw new ItemNotStoredException("Location has seats activated, you need to set a seatNumber!");
        } elseif ($seatNumber !== null && $maximumPhysicalAttendeeCapacity === null) {
            throw new ItemNotStoredException("Location doesn't have any seats activated, you cannot set a seatNumber!");
        } elseif ($seatNumber !== null && $seatNumber > $maximumPhysicalAttendeeCapacity) {
            throw new ItemNotStoredException("seatNumber must not exceed maximumPhysicalAttendeeCapacity of location!");
        } elseif ($seatNumber !== null && $seatNumber < 1) {
            throw new ItemNotStoredException("seatNumber too low!");
        }
    }

    /**
     * @param string $configKey
     * @return mixed
     * @throws ItemNotLoadedException
     * @throws AccessDeniedHttpException
     */
    public function fetchConfig(string $configKey) {
        $client = $this->getLocationClient();

        $options = [
            'headers' => [ 'X-Authorization' => $this->campusQRToken ],
        ];

        try {
            $url = $this->urls->getConfigUrl($this->campusQRUrl, $configKey);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                throw new AccessDeniedHttpException('The access token is not allowed to fetch config!');
            }

            throw new ItemNotLoadedException(sprintf('Config could not be loaded: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotLoadedException(sprintf('Config could not be loaded: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @param \DateTime|null $date
     * @return \DateTime
     * @throws ItemNotLoadedException
     */
    public function fetchMaxCheckInEndTime(\DateTime $date = null): \DateTime {
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

    public function createAndDispatchLocationGuestCheckOutMessage(LocationGuestCheckInAction $locationGuestCheckInAction)
    {
        $message = new LocationGuestCheckOutMessage(
            $locationGuestCheckInAction->getEmail(),
            $locationGuestCheckInAction->getLocation(),
            $locationGuestCheckInAction->getSeatNumber()
        );

        $this->bus->dispatch(
            $message, [
            $this->getDelayStampFromLocationGuestCheckInAction($locationGuestCheckInAction),
        ]);

        return $message;
    }

    /**
     * @param LocationGuestCheckInAction $locationGuestCheckInAction
     * @return DelayStamp
     */
    public function getDelayStampFromLocationGuestCheckInAction(LocationGuestCheckInAction $locationGuestCheckInAction): DelayStamp {
        $endTime = $locationGuestCheckInAction->getEndTime();
        $seconds = $endTime->getTimestamp() - time();

        if ($seconds < 0) {
            $seconds = 0;
        }

        return new DelayStamp($seconds * 1000);
    }

    /**
     * Handles the delayed checkout of guests
     *
     * @param LocationGuestCheckOutMessage $message
     */
    public function handleLocationGuestCheckOutMessage(LocationGuestCheckOutMessage $message) {
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
}
