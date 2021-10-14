<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\MessageHandler;

use Dbp\Relay\CheckinBundle\Message\GuestCheckOutMessage;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GuestCheckOutHandler implements MessageHandlerInterface
{
    private $api;

    public function __construct(CheckinApi $api)
    {
        $this->api = $api;
    }

    public function __invoke(GuestCheckOutMessage $message)
    {
        $this->api->handleGuestCheckOutMessage($message);
    }
}
