<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Message;

use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;

class LocationGuestCheckOutMessage
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var CheckInPlace
     */
    private $location;

    /**
     * @var string
     */
    private $seatNumber;

    /**
     * LocationGuestCheckOutMessage constructor.
     *
     * @param $email string
     * @param $location CheckInPlace
     * @param $seatNumber ?int
     */
    public function __construct(
        string $email,
        CheckInPlace $location,
        ?int $seatNumber
    )
    {
        $this->email = $email;
        $this->location = $location;
        $this->seatNumber = $seatNumber;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLocation(): CheckInPlace
    {
        return $this->location;
    }

    public function getSeatNumber(): int
    {
        return $this->seatNumber;
    }
}
