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
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/location_check_in_actions",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "normalization_context" = {
 *                 "groups" = {"LocationCheckIn:output", "LocationCheckIn:outputList"}
 *             },
 *             "openapi_context" = {
 *                 "tags" = {"LocationCheckIn"},
 *                 "summary" = "Retrieves all LocationCheckInActions of the current user.",
 *                 "parameters" = {
 *                     {
 *                         "name" = "location",
 *                         "in" = "query",
 *                         "description" = "Location",
 *                         "type" = "string",
 *                         "example" = "f0ad66aaaf1debabb44a"
 *                     }
 *                 }
 *             },
 *         },
 *         "post" = {
 *             "path" = "/location_check_in_actions",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "method" = "POST",
 *             "openapi_context" = {
 *                 "tags" = {"LocationCheckIn"},
 *                 "requestBody" = {
 *                     "content" = {
 *                         "application/json" = {
 *                             "schema" = {"type" = "object"},
 *                             "example" = {"location" = "/check_in_places/f0ad66aaaf1debabb44a", "seatNumber" = 17}
 *                         }
 *                     }
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/location_check_in_actions/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "openapi_context" = {
 *                 "tags" = {"LocationCheckIn"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/CheckInAction",
 *     description="Location check-in action",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"LocationCheckIn:output", "CheckInPlace:output"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"LocationCheckIn:input"}
 *     }
 * )
 */
class LocationCheckInAction
{
    /**
     * @Groups({"LocationCheckIn:output"})
     * @ApiProperty(identifier=true, iri="http://schema.org/identifier")
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
     * @Assert\NotBlank
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
     * @ApiProperty(iri="https://schema.org/startTime")
     * @Groups({"LocationCheckIn:output"})
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * @ApiProperty(iri="https://schema.org/endTime")
     * @Groups({"LocationCheckIn:output"})
     *
     * @var \DateTime
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

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }
}
