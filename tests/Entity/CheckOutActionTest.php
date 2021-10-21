<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Tests\Entity;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CheckinBundle\Entity\CheckOutAction;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckOutActionTest extends WebTestCase
{
    public function testBasics()
    {
        $action = new CheckOutAction();
        $action->setLocation(new Place());
        $action->setAgent(new Person());
        $seatNumber = $action->getSeatNumber();
        $this->assertNull($seatNumber);
    }
}
