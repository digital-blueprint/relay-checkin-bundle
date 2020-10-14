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

    public function test_getLocationRequestUrl()
    {
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far/visit', $this->urls->getLocationRequestUrl($this->campusQRUrl, 'foob?ar'));
        $this->assertEquals($this->campusQRUrl . '/location/foob%3Far-22/visit', $this->urls->getLocationRequestUrl($this->campusQRUrl, 'foob?ar', 22));
    }
}
