<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;

class Test extends ApiTestCase
{
    /** @var Client */
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testIndex()
    {
        $response = $this->client->request('GET', '/checkin/check_in_actions');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testJSONLD()
    {
        $response = $this->client->request('GET', '/checkin/check_in_actions', ['headers' => ['HTTP_ACCEPT' => 'application/ld+json']]);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertJson($response->getContent(false));
    }

    public function testNotAuth()
    {
        $endpoints = [
            ['POST', '/checkin/check_in_actions', 401],
            ['GET', '/checkin/check_in_actions', 401],
            ['POST', '/checkin/check_out_actions', 401],
            ['POST', '/checkin/guest_check_in_actions', 401],
            ['GET', '/checkin/places', 401],
            ['GET', '/checkin/places/42', 401],
        ];

        foreach ($endpoints as $ep) {
            [$method, $path, $status] = $ep;
            $client = self::createClient();
            $response = $client->request($method, $path);
            $this->assertEquals($status, $response->getStatusCode(), $path);
        }
    }
}
