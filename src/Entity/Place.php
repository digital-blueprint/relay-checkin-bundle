<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Note: We need a "collectionOperations" setting for "get" to get an "entryPoint" in JSONLD.
 *
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/checkin/places",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *                 "parameters" = {
 *                     {"name" = "search", "in" = "query", "description" = "Search for a place name", "type" = "string", "example" = "Coworkingspace"}
 *                 }
 *             }
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/checkin/places/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/Place",
 *     description="Check-in place",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"Place:output", "Checkin:outputList"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"Place:input"}
 *     }
 * )
 */
class Place
{
    /**
     * @Groups({"Place:output", "Checkin:outputList"})
     * @ApiProperty(identifier=true, iri="http://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"Place:output", "Checkin:outputList"})
     *
     * @var string
     */
    private $name;

    /**
     * @ApiProperty(iri="http://schema.org/maximumPhysicalAttendeeCapacity")
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
