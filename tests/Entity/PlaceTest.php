<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Tests\Entity;

use Dbp\Relay\CheckinBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlaceTest extends WebTestCase
{
    public function testBasics()
    {
        $place = new Place();
        $place->setName('Test');
        $this->assertNull($place->getMaximumPhysicalAttendeeCapacity());
    }
}
