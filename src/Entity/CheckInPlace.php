<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Note: We need a "collectionOperations" setting for "get" to get an "entryPoint" in JSONLD.
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get" = {
 *             "openapi_context" = {
 *                 "parameters" = {
 *                     {"name" = "search", "in" = "query", "description" = "Search for a place name", "type" = "string", "example" = "Coworkingspace"}
 *                 }
 *             }
 *         },
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     iri="http://schema.org/Place",
 *     description="Check-in place",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"CheckInPlace:output", "LocationCheckIn:outputList"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"CheckInPlace:input"}
 *     }
 * )
 */
class CheckInPlace
{
    /**
     * @Groups({"CheckInPlace:output", "LocationCheckIn:outputList"})
     * @ApiProperty(identifier=true, iri="http://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"CheckInPlace:output", "LocationCheckIn:outputList"})
     *
     * @var string
     */
    private $name;

    /**
     * @ApiProperty(iri="http://schema.org/maximumPhysicalAttendeeCapacity")
     * @Groups({"CheckInPlace:output"})
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
