<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Tests;

use Dbp\Relay\CheckinBundle\Service\CheckinUrlApi;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckInApiUrlTest extends WebTestCase
{
    private CheckinUrlApi $urls;

    private string $campusQRUrl = '';

    protected function setUp(): void
    {
        $this->urls = new CheckinUrlApi();
        $this->campusQRUrl = 'http://test';
    }

    public function testGetCheckInRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl.'/location/foob%3Far/visit', $this->urls->getCheckInRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl.'/location/foob%3Far-22/visit', $this->urls->getCheckInRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }

    public function testGetGuestCheckInRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl.'/location/foob%3Far/guestCheckInBy',
            $this->urls->getGuestCheckInRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl.'/location/foob%3Far-22/guestCheckInBy',
            $this->urls->getGuestCheckInRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }

    public function testGetCheckOutRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl.'/location/foob%3Far/checkoutSeat', $this->urls->getCheckOutRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl.'/location/foob%3Far-22/checkoutSeat', $this->urls->getCheckOutRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }

    public function testGetLocationListRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl.'/location/list', $this->urls->getLocationListRequestUrl($this->campusQRUrl));
    }

    public function testGetCheckInActionListOfCurrentPersonRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl.'/report/listActiveCheckIns', $this->urls->getCheckInActionListOfCurrentPersonRequestUrl($this->campusQRUrl));
    }

    public function testGetConfigUrl()
    {
        $this->assertEquals($this->campusQRUrl.'/config/get?id=foob%3Far', $this->urls->getConfigUrl($this->campusQRUrl, 'foob?ar'));
    }
}
