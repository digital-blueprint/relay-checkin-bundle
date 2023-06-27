<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CheckInAction
{
    /**
     * @Groups({"Checkin:output"})
     */
    private $identifier;

    /**
     * @Groups({"Checkin:output"})
     *
     * @var Person
     */
    private $agent;

    /**
     * @Groups({"Checkin:output", "Checkin:input"})
     * @Assert\NotBlank
     *
     * @var Place
     */
    private $location;

    /**
     * @Groups({"Checkin:output", "Checkin:input"})
     *
     * @var ?int
     */
    private $seatNumber;

    /**
     * @Groups({"Checkin:output"})
     *
     * @var \DateTimeInterface
     */
    private $startTime;

    /**
     * @Groups({"Checkin:output"})
     *
     * @var \DateTimeInterface
     */
    private $endTime;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getAgent(): Person
    {
        return $this->agent;
    }

    public function setAgent(Person $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getLocation(): Place
    {
        return $this->location;
    }

    public function setLocation(Place $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getSeatNumber(): ?int
    {
        return $this->seatNumber;
    }

    public function setSeatNumber(?int $seatNumber): self
    {
        $this->seatNumber = $seatNumber;

        return $this;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }
}
