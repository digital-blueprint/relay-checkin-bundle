<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Place
{
    /**
     * @Groups({"Place:output", "Checkin:outputList"})
     */
    private $identifier;

    /**
     * @Groups({"Place:output", "Checkin:outputList"})
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"Place:output"})
     *
     * @var ?int
     */
    private $maximumPhysicalAttendeeCapacity;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMaximumPhysicalAttendeeCapacity(): ?int
    {
        return $this->maximumPhysicalAttendeeCapacity;
    }

    public function setMaximumPhysicalAttendeeCapacity(int $maximumPhysicalAttendeeCapacity): self
    {
        $this->maximumPhysicalAttendeeCapacity = $maximumPhysicalAttendeeCapacity;

        return $this;
    }
}
