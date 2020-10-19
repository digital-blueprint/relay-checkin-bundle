<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DBP\API\CoreBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Note: We need a "collectionOperations" setting for "get" to get an "entryPoint" in JSONLD.
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *             "normalization_context"={"groups"={"LocationCheckIn:output", "LocationCheckIn:outputList"}},
 *             "openapi_context"={
 *                 "summary"="Retrieves all LocationGuestCheckInActions of the current user.",
 *                 "parameters"={
 *                    {
 *                      "name"="location",
 *                      "in"="query",
 *                      "description"="Location",
 *                      "type"="string",
 *                      "example"="00e5de0fc311d30575ea"
 *                    }
 *                 }
 *             },
 *         },
 *         "post"={
 *             "method"="POST",
 *             "openapi_context"={
 *                 "parameters"={
 *                    {
 *                      "name"="body",
 *                      "in"="body",
 *                      "description"="Location",
 *                      "type"="string",
 *                      "example"={"location"="/check_in_places/00e5de0fc311d30575ea", "seatNumber"=17},
 *                      "required"="true"
 *                    }
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={"get"},
 *     iri="http://schema.org/CheckInAction",
 *     description="Location guest check-in action",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LocationCheckIn:output", "CheckInPlace:output"}},
 *     denormalizationContext={"groups"={"LocationCheckIn:input"}}
 * )
 */
class LocationGuestCheckInAction
{
    /**
     * @Groups({"LocationCheckIn:output"})
     * @ApiProperty(identifier=true,iri="http://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/Person")
     * @Groups({"LocationCheckIn:output"})
     *
     * @var Person
     */
    private $agent;

    /**
     * @ApiProperty(iri="http://schema.org/location")
     * @Groups({"LocationCheckIn:output", "LocationCheckIn:input"})
     *
     * @var CheckInPlace
     */
    private $location;

    /**
     * @ApiProperty(iri="http://schema.org/seatNumber")
     * @Groups({"LocationCheckIn:output", "LocationCheckIn:input"})
     *
     * @var ?int
     */
    private $seatNumber;

    /**
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LocationCheckIn:output"})
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LocationCheckIn:output", "LocationCheckIn:input"})
     *
     * @var \DateTime
     */
    private $endTime;

    /**
     * @ApiProperty(iri="http://schema.org/email")
     * @Groups({"LocationCheckIn:output", "LocationCheckIn:input"})
     *
     * @var string
     */
    private $email;

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

    public function getLocation(): CheckInPlace
    {
        return $this->location;
    }

    public function setLocation(CheckInPlace $location): self
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

    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}