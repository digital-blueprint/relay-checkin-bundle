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
 *     collectionOperations={"get",
 *         "post"={
 *             "method"="POST",
 *             "openapi_context"={
 *                 "parameters"={
 *                    {"name"="body", "in"="body", "description"="Location", "type"="string", "example"={"location"="c65200af79517a925d44"}, "required"="true"}
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={"get"},
 *     iri="http://schema.org/CheckInAction",
 *     description="Location check-in action",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LocationCheckIn:output"}},
 *     denormalizationContext={"groups"={"LocationCheckIn:input"}}
 * )
 */
class LocationCheckInAction
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
     * @var string
     */
    private $location;

    /**
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LocationCheckIn:output"})
     *
     * @var \DateTime
     */
    private $startTime;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getAgent(): ?Person
    {
        return $this->agent;
    }

    public function setAgent(Person $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }
}
