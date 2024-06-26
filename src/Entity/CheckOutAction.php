<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CheckOutAction
{
    #[Groups(['CheckOut:output'])]
    private $identifier;

    /**
     * @var Person
     */
    #[Groups(['CheckOut:output'])]
    private $agent;

    /**
     * @var Place
     */
    #[Groups(['CheckOut:output', 'CheckOut:input'])]
    #[Assert\NotBlank]
    private $location;

    /**
     * @var ?int
     */
    #[Groups(['CheckOut:output', 'CheckOut:input'])]
    private $seatNumber;

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
}
