<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DBP\API\CoreBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Note: We need a "collectionOperations" setting for "get" to get an "entryPoint" in JSONLD.
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *         "post"={
 *             "method"="POST",
 *             "openapi_context"={
 *                 "parameters"={
 *                    {
 *                      "name"="body",
 *                      "in"="body",
 *                      "description"="Location",
 *                      "type"="string",
 *                      "example"={"location"="/check_in_places/f0ad66aaaf1debabb44a", "seatNumber"=17},
 *                      "required"="true"
 *                    }
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={"get"},
 *     iri="http://schema.org/CheckOutAction",
 *     description="Location check-out action",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LocationCheckOut:output", "CheckInPlace:output"}},
 *     denormalizationContext={"groups"={"LocationCheckOut:input"}}
 * )
 */
class LocationCheckOutAction
{
    /**
     * @Groups({"LocationCheckOut:output"})
     * @ApiProperty(identifier=true,iri="http://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/Person")
     * @Groups({"LocationCheckOut:output"})
     *
     * @var Person
     */
    private $agent;

    /**
     * @ApiProperty(iri="http://schema.org/location")
     * @Groups({"LocationCheckOut:output", "LocationCheckOut:input"})
     * @Assert\NotBlank
     *
     * @var CheckInPlace
     */
    private $location;

    /**
     * @ApiProperty(iri="http://schema.org/seatNumber")
     * @Groups({"LocationCheckOut:output", "LocationCheckOut:input"})
     *
     * @var ?int
     */
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
}
