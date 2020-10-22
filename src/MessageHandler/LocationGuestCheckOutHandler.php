<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\MessageHandler;

use DBP\API\LocationCheckInBundle\Message\LocationGuestCheckOutMessage;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class LocationGuestCheckOutHandler implements MessageHandlerInterface
{
    private $api;

    public function __construct(LocationCheckInApi $api)
    {
        $this->api = $api;
    }

    public function __invoke(LocationGuestCheckOutMessage $message)
    {
        $this->api->handleLocationGuestCheckOutMessage($message);
    }
}
