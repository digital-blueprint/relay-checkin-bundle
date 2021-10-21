<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Tests\Entity;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckInActionTest extends WebTestCase
{
    public function testBasics()
    {
        $action = new CheckInAction();
        $action->setLocation(new Place());
        $action->setAgent(new Person());
        $seatNumber = $action->getSeatNumber();
        $this->assertNull($seatNumber);
    }
}
