<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests\Entity;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocationCheckOutActionTest extends WebTestCase
{
    public function testBasics()
    {
        $action = new LocationCheckOutAction();
        $action->setLocation(new CheckInPlace());
        $action->setAgent(new Person());
        $seatNumber = $action->getSeatNumber();
        $this->assertNull($seatNumber);
    }
}
