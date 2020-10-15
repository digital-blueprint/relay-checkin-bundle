<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInUrlApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocationCheckInApiTest extends WebTestCase
{
    /**
     * @var LocationCheckInApi
     */
    private $api;

    protected function setUp(): void
    {
        $client = static::createClient();
        $nullLogger = new Logger('dummy', [new NullHandler()]);
        $guzzleLogger = new GuzzleLogger($nullLogger);

        $person = new Person();
        $personProvider = new DummyPersonProvider($person);

        $this->api = new LocationCheckInApi($guzzleLogger, $personProvider, $client->getContainer());
        $this->api->setCampusQRUrl("http://dummy");
        $this->api->setCampusQRToken("dummy");
        $this->mockResponses([]);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->api->setClientHandler($stack);
    }

    public function test_sendCampusQRCheckInRequest()
    {
        $action = new LocationCheckInAction();
        $action->setAgent(new Person());

        $location = new CheckInPlace();
        $location->setIdentifier("dummy");
        $action->setLocation($location);

        $this->mockResponses([
            new Response(200, [], 'ok'),
        ]);

        $result = $this->api->sendCampusQRCheckInRequest($action);

        $this->assertTrue($result);
    }
}
