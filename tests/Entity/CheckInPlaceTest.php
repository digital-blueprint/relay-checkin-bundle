<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests\Entity;

use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckInPlaceTest extends WebTestCase
{
    public function testBasics()
    {
        $place = new CheckInPlace();
        $place->setName('Test');
        $this->assertNull($place->getMaximumPhysicalAttendeeCapacity());
    }
}
