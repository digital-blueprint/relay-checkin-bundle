<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Message;

use Dbp\Relay\CheckinBundle\Entity\Place;

class GuestCheckOutMessage
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var Place
     */
    private $location;

    /**
     * @var int
     */
    private $seatNumber;

    /**
     * GuestCheckOutMessage constructor.
     *
     * @param ?int $seatNumber
     */
    public function __construct(
        string $email,
        Place $location,
        ?int $seatNumber
    ) {
        $this->email = $email;
        $this->location = $location;
        $this->seatNumber = $seatNumber;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLocation(): Place
    {
        return $this->location;
    }

    public function getSeatNumber(): ?int
    {
        return $this->seatNumber;
    }
}
