<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests;

use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Dbp\Relay\BaseBundle\Entity\Person;
use Dbp\Relay\BaseBundle\TestUtils\DummyPersonProvider;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;

class LocationCheckInApiTest extends WebTestCase
{
    /**
     * @var LocationCheckInApi
     */
    private $api;

    private const placesResponse = '[{"id":"testLocation","name":"Test Location","checkInCount":50,"accessType":"FREE","seatCount":null},{"id":"f0ad66aaaf1debabb44a","name":"Brockmanngasse 84 Coworkingspace","checkInCount":280,"accessType":"FREE","seatCount":70}]';
    private const listActiveCheckInsResponse = '[{"id":"280ceccd269f5527603c3acbfc416dbb","locationId":"f0ad66aaaf1debabb44a","locationName":"Brockmanngasse 84 Coworkingspace","seat":17,"checkInDate":1.602763809372E12,"email":"test@test.com"}]';

    protected function setUp(): void
    {
        $person = new Person();
        $person->setEmail('dummy@email.com');
        $personProvider = new DummyPersonProvider($person);

        /** @var MessageBusInterface $messageBus */
        $messageBus = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var LockFactory $lockFactory */
        $lockFactory = $this->getMockBuilder(LockFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->api = new LocationCheckInApi($personProvider, $messageBus, $lockFactory);
        $this->api->setCampusQRUrl('http://dummy');
        $this->api->setCampusQRToken('dummy');
        $this->mockResponses([]);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->api->setClientHandler($stack);
    }

    public function testSendCampusQRCheckInRequest()
    {
        $action = new LocationCheckInAction();
        $action->setAgent(new Person());

        $location = new CheckInPlace();
        $location->setIdentifier('dummy');
        $action->setLocation($location);

        $this->mockResponses([
            new Response(200, [], 'ok'),
        ]);

        $result = $this->api->sendCampusQRCheckInRequest($action);

        $this->assertTrue($result);
    }

    public function testSendCampusQRCheckOutRequestForLocationCheckOutAction()
    {
        $action = new LocationCheckOutAction();

        $person = new Person();
        $person->setEmail('dummy@email.com');
        $action->setAgent($person);

        $location = new CheckInPlace();
        $location->setIdentifier('dummy');
        $action->setLocation($location);

        $this->mockResponses([
            new Response(200, [], 'ok'),
        ]);

        $result = $this->api->sendCampusQRCheckOutRequestForLocationCheckOutAction($action);

        $this->assertTrue($result);
    }

    public function testFetchCheckInPlaces()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces();

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0] instanceof CheckInPlace);
    }

    public function testFetchCheckInPlacesByName()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces('Brock 84');

        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInPlace);
        $this->assertEquals($result[0]->getName(), 'Brockmanngasse 84 Coworkingspace');
        $this->assertEquals($result[0]->getMaximumPhysicalAttendeeCapacity(), 70);
    }

    public function testFetchCheckInPlacesNameNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces('Brock 100');

        $this->assertCount(0, $result);
    }

    public function testFetchCheckInPlacesEmptyCapacity()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlaces('test');

        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInPlace);
        $this->assertEquals($result[0]->getName(), 'Test Location');
        $this->assertNull($result[0]->getMaximumPhysicalAttendeeCapacity());
    }

    public function testFetchCheckInPlace()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchCheckInPlace('f0ad66aaaf1debabb44a');

        $this->assertTrue($result instanceof CheckInPlace);
        $this->assertEquals($result->getName(), 'Brockmanngasse 84 Coworkingspace');
        $this->assertEquals($result->getMaximumPhysicalAttendeeCapacity(), 70);
    }

    public function testFetchCheckInPlaceNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        try {
            $this->api->fetchCheckInPlace('wrong');
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Location was not found!', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testFetchLocationCheckInActionsOfCurrentPerson()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180), // for LocationCheckInApi::fetchMaxCheckInEndTime
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson();

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof LocationCheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime('2020-10-15 14:10:09'));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function testFetchLocationCheckInActionsOfCurrentPersonWithLocation()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson('f0ad66aaaf1debabb44a');

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof LocationCheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime('2020-10-15 14:10:09'));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function testFetchLocationCheckInActionsOfCurrentPersonWithLocationNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson('wrong');

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(0, $result);
    }

    public function testFetchLocationCheckInActionsOfCurrentPersonWithLocationAndSeat()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson('f0ad66aaaf1debabb44a', 17);

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof LocationCheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime('2020-10-15 14:10:09'));
        $this->assertEquals(17, $result[0]->getSeatNumber());
    }

    public function testFetchLocationCheckInActionsOfCurrentPersonWithLocationAndSeatNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180),
        ]);

        $result = $this->api->fetchLocationCheckInActionsOfCurrentPerson('f0ad66aaaf1debabb44a', 18);

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(0, $result);
    }
}
