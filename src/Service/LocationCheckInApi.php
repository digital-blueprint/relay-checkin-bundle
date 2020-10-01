<?php

declare(strict_types=1);
/**
 * LocationCheckIn API service.
 */

namespace DBP\API\LocationCheckInBundle\Service;

use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use League\Uri\Contracts\UriException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LocationCheckInApi
{
    private $clientHandler;

    private $guzzleLogger;

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


    public function __construct(
        GuzzleLogger $guzzleLogger,
        PersonProviderInterface $personProvider,
        ContainerInterface $container
    )
    {
        $this->clientHandler = null;
        $this->guzzleLogger = $guzzleLogger;
        $this->personProvider = $personProvider;
        $this->urls = new LocationCheckInUrlApi();

        $config = $container->getParameter('dbp_api.location_check_in.config');
        $this->campusQRUrl = $config['campus_qr_url'] ?? '';
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

        $stack->push($this->guzzleLogger->getClientHandler());

        return new Client($client_options);
    }

    /**
     * @param LocationCheckInAction $locationCheckInAction
     * @return bool
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function sendCampusQRLocationRequest(LocationCheckInAction $locationCheckInAction): bool {
        $location = $locationCheckInAction->getLocation();
        $person = $locationCheckInAction->getAgent();

        $client = $this->getClient();
        $options = [
            'body' => json_encode(['email' => $person->getEmail()])
        ];

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/c65200af79517a925d44/visit
            $url = $this->urls->getLocationRequestUrl($this->campusQRUrl, $location);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url, $options);

            $body = $response->getBody()->getContents();

            return $body === "ok";
        } catch (GuzzleException $e) {
            $status = $e->getCode();

            if ($status == 403) {
                dump($status);
                throw new AccessDeniedHttpException('You are not allowed to check-in at this location!');
            }

            throw new ItemNotStoredException(sprintf('LocationCheckIn could not be stored: %s',
                Tools::filterErrorMessage($e->getMessage())));
        } catch (\Exception|UriException $e) {
            throw new ItemNotStoredException(sprintf('LocationCheckIn could not be stored: %s',
                Tools::filterErrorMessage($e->getMessage())));
        }
    }
 }
