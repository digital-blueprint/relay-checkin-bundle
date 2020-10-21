<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocationCheckInApiTest extends WebTestCase
{
    /**
     * @var LocationCheckInApi
     */
    private $api;

    private const placesResponse = '[{"id":"testLocation","name":"Test Location","checkInCount":50,"accessType":"FREE","seatCount":null},{"id":"a1ef83b6f42a5aa3b77f","name":"Brockmanngasse 84 Coworkingspace","checkInCount":280,"accessType":"FREE","seatCount":70}]';
    private const listActiveCheckInsResponse = '[{"id":"280ceccd269f5527603c3acbfc416dbb","locationId":"a1ef83b6f42a5aa3b77f","locationName":"Brockmanngasse 84 Coworkingspace","seat":17,"checkInDate":1.602763809372E12,"email":"test@test.com"}]';

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

    public function test_sendCampusQRCheckOutRequest()
    {
        $action = new LocationCheckOutAction();
        $action->setAgent(new Person());

        $location = new CheckInPlace();
        $location->setIdentifier("dummy");
        $action->setLocation($location);

        $this->mockResponses([
            new Response(200, [], 'ok'),
        ]);

        $result = $this->api->sendCampusQRCheckOutRequest($action);

        $this->assertTrue($result);
    }

    public function test_fetchCheckInPlaces()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces();

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0] instanceof CheckInPlace);
    }

    public function test_fetchCheckInPlacesByName()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces("Brock 84");

        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInPlace);
        $this->assertEquals($result[0]->getName(), "Brockmanngasse 84 Coworkingspace");
        $this->assertEquals($result[0]->getMaximumPhysicalAttendeeCapacity(), 70);
    }

    public function test_fetchCheckInPlacesNameNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces("Brock 100");

        $this->assertCount(0, $result);
    }

    public function test_fetchCheckInPlacesEmptyCapacity()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces("test");

        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInPlace);
        $this->assertEquals($result[0]->getName(), "Test Location");
        $this->assertNull($result[0]->getMaximumPhysicalAttendeeCapacity());
    }

    public function test_fetchCheckInPlace()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlace("a1ef83b6f42a5aa3b77f");

        $this->assertTrue($result instanceof CheckInPlace);
        $this->assertEquals($result->getName(), "Brockmanngasse 84 Coworkingspace");
        $this->assertEquals($result->getMaximumPhysicalAttendeeCapacity(), 70);
    }

    public function test_fetchCheckInPlaceNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        try {
            $this->api->fetchCheckInPlace("wrong");
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Location was not found!', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function test_fetchLocationCheckInActionsOfCurrentPerson()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson();

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof LocationCheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime("2020-10-15 14:10:09"));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function test_fetchLocationCheckInActionsOfCurrentPersonWithLocation()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson("a1ef83b6f42a5aa3b77f");

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof LocationCheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime("2020-10-15 14:10:09"));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function test_fetchLocationCheckInActionsOfCurrentPersonWithLocationNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson("wrong");

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(0, $result);
    }

    public function test_fetchLocationCheckInActionsOfCurrentPersonWithLocationAndSeat()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson("a1ef83b6f42a5aa3b77f", 17);

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof LocationCheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime("2020-10-15 14:10:09"));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function test_fetchLocationCheckInActionsOfCurrentPersonWithLocationAndSeatNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson("a1ef83b6f42a5aa3b77f", 18);

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(0, $result);
    }
}
