<?php

declare(strict_types=1);
/**
 * LocationCheckIn API service.
 */

namespace DBP\API\LocationCheckInBundle\Service;

use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * @param $location
     * @return bool
     * @throws GuzzleException
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function sendCampusQRLocationRequest($location): bool {
        // e.g. https://campusqr-dev.tugraz.at/location/c65200af79517a925d44/visit
        $url = $this->urls->getLocationRequestUrl($this->campusQRUrl, $location);

        $client = $this->getClient();

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $url);

            $body = $response->getBody();
            dump($body);

            return $body === "ok";
        } catch (\Exception $e) {
            throw new ItemNotLoadedException(sprintf('Campus QR request failed: %s', $e->getMessage()));
        }
    }
 }
