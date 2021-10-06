<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests\Entity;

use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use Dbp\Relay\BaseBundle\Entity\Person;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocationCheckInActionTest extends WebTestCase
{
    public function testBasics()
    {
        $action = new LocationCheckInAction();
        $action->setLocation(new CheckInPlace());
        $action->setAgent(new Person());
        $seatNumber = $action->getSeatNumber();
        $this->assertNull($seatNumber);
    }
}
