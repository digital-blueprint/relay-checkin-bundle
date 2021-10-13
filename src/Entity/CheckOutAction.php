<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Dbp\Relay\BaseBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Note: We need a "collectionOperations" setting for "get" to get an "entryPoint" in JSONLD.
 *
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/checkin/check_out_actions",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *             },
 *         },
 *         "post" = {
 *             "path" = "/checkin/check_out_actions",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "method" = "POST",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *                 "requestBody" = {
 *                     "content" = {
 *                         "application/json" = {
 *                             "schema" = {"type" = "object"},
 *                             "example" = {"location" = "/checkin/places/f0ad66aaaf1debabb44a", "seatNumber" = 17}
 *                         }
 *                     }
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/checkin/check_out_actions/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/CheckOutAction",
 *     description="Location check-out action",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"CheckOut:output", "Place:output"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"CheckOut:input"}
 *     }
 * )
 */
class CheckOutAction
{
    /**
     * @Groups({"CheckOut:output"})
     * @ApiProperty(identifier=true, iri="http://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/Person")
     * @Groups({"CheckOut:output"})
     *
     * @var Person
     */
    private $agent;

    /**
     * @ApiProperty(iri="http://schema.org/location")
     * @Groups({"CheckOut:output", "CheckOut:input"})
     * @Assert\NotBlank
     *
     * @var Place
     */
    private $location;

    /**
     * @ApiProperty(iri="http://schema.org/seatNumber")
     * @Groups({"CheckOut:output", "CheckOut:input"})
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
