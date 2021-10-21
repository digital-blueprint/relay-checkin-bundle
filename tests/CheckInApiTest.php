<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Tests;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\BasePersonBundle\TestUtils\DummyPersonProvider;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Entity\CheckOutAction;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;

class CheckinApiTest extends WebTestCase
{
    /**
     * @var CheckinApi
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

        $this->api = new CheckinApi($personProvider, $messageBus, $lockFactory);
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
        $action = new CheckInAction();
        $action->setAgent(new Person());

        $location = new Place();
        $location->setIdentifier('dummy');
        $action->setLocation($location);

        $this->mockResponses([
            new Response(200, [], 'ok'),
        ]);

        $result = $this->api->sendCampusQRCheckInRequest($action);

        $this->assertTrue($result);
    }

    public function testSendCampusQRCheckOutRequestForCheckOutAction()
    {
        $action = new CheckOutAction();

        $person = new Person();
        $person->setEmail('dummy@email.com');
        $action->setAgent($person);

        $location = new Place();
        $location->setIdentifier('dummy');
        $action->setLocation($location);

        $this->mockResponses([
            new Response(200, [], 'ok'),
        ]);

        $result = $this->api->sendCampusQRCheckOutRequestForCheckOutAction($action);

        $this->assertTrue($result);
    }

    public function testFetchPlaces()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchPlaces();

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0] instanceof Place);
    }

    public function testFetchPlacesByName()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchPlaces('Brock 84');

        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof Place);
        $this->assertEquals($result[0]->getName(), 'Brockmanngasse 84 Coworkingspace');
        $this->assertEquals($result[0]->getMaximumPhysicalAttendeeCapacity(), 70);
    }

    public function testFetchPlacesNameNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchPlaces('Brock 100');

        $this->assertCount(0, $result);
    }

    public function testFetchPlacesEmptyCapacity()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchPlaces('test');

        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof Place);
        $this->assertEquals($result[0]->getName(), 'Test Location');
        $this->assertNull($result[0]->getMaximumPhysicalAttendeeCapacity());
    }

    public function testFetchPlace()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        $result = $this->api->fetchPlace('f0ad66aaaf1debabb44a');

        $this->assertTrue($result instanceof Place);
        $this->assertEquals($result->getName(), 'Brockmanngasse 84 Coworkingspace');
        $this->assertEquals($result->getMaximumPhysicalAttendeeCapacity(), 70);
    }

    public function testFetchPlaceNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::placesResponse),
        ]);

        try {
            $this->api->fetchPlace('wrong');
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Location was not found!', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testFetchCheckInActionsOfCurrentPerson()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180), // for CheckinApi::fetchMaxCheckinEndTime
        ]);

        $result = $this->api->fetchCheckInActionsOfCurrentPerson();

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime('2020-10-15 14:10:09'));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function testFetchCheckInActionsOfCurrentPersonWithLocation()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180),
        ]);

        $result = $this->api->fetchCheckInActionsOfCurrentPerson('f0ad66aaaf1debabb44a');

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime('2020-10-15 14:10:09'));
        $this->assertEquals($result[0]->getSeatNumber(), 17);
    }

    public function testFetchCheckInActionsOfCurrentPersonWithLocationNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
        ]);

        $result = $this->api->fetchCheckInActionsOfCurrentPerson('wrong');

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(0, $result);
    }

    public function testFetchCheckInActionsOfCurrentPersonWithLocationAndSeat()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180),
        ]);

        $result = $this->api->fetchCheckInActionsOfCurrentPerson('f0ad66aaaf1debabb44a', 17);

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof CheckInAction);
        $this->assertEquals($result[0]->getStartTime(), new \DateTime('2020-10-15 14:10:09'));
        $this->assertEquals(17, $result[0]->getSeatNumber());
    }

    public function testFetchCheckInActionsOfCurrentPersonWithLocationAndSeatNotFound()
    {
        $this->mockResponses([
            new Response(200, [], self::listActiveCheckInsResponse),
            new Response(200, [], 180),
        ]);

        $result = $this->api->fetchCheckInActionsOfCurrentPerson('f0ad66aaaf1debabb44a', 18);

        $this->assertTrue($result instanceof ArrayCollection);
        $this->assertCount(0, $result);
    }
}
