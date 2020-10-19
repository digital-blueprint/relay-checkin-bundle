<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Tests;

use DBP\API\LocationCheckInBundle\Service\LocationCheckInUrlApi;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocationCheckInApiUrlTest extends WebTestCase
{
    /* @var LocationCheckInUrlApi */
    private $urls;

    private $campusQRUrl;

    protected function setUp(): void
    {
        $this->urls = new LocationCheckInUrlApi();
        $this->campusQRUrl = "http://test";
    }

    public function test_getCheckInRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far/visit', $this->urls->getCheckInRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far-22/visit', $this->urls->getCheckInRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }

    public function test_getGuestCheckInRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far/guestCheckIn', $this->urls->getGuestCheckInRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far-22/guestCheckIn', $this->urls->getGuestCheckInRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }

    public function test_getCheckOutRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far/checkoutSeat', $this->urls->getCheckOutRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far-22/checkoutSeat', $this->urls->getCheckOutRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }

    public function test_getLocationListRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl . '/location/list', $this->urls->getLocationListRequestUrl($this->campusQRUrl));
    }

    public function test_getLocationCheckInActionListOfCurrentPersonRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl . '/report/listActiveCheckIns', $this->urls->getLocationCheckInActionListOfCurrentPersonRequestUrl($this->campusQRUrl));
    }
}
