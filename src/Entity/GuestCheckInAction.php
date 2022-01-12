<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Dbp\Relay\BasePersonBundle\Entity\Person;
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
 *             "path" = "/checkin/guest_check_in_actions",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "normalization_context" = {
 *                 "groups" = {"Checkin:output", "Checkin:outputList"}
 *             },
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *                 "summary" = "Retrieves all GuestCheckInActions of the current user.",
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
 *             "path" = "/checkin/guest_check_in_actions",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "method" = "POST",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *                 "requestBody" = {
 *                     "content" = {
 *                         "application/json" = {
 *                             "schema" = {"type" = "object"},
 *                             "example" = {"location" = "/checkin/places/f0ad66aaaf1debabb44a", "seatNumber" = 17, "email" = "test@test.com", "endTime" = "2021-10-19T08:03:11.336Z"}
 *                         }
 *                     }
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/checkin/guest_check_in_actions/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *             "openapi_context" = {
 *                 "tags" = {"Checkin"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/CheckInAction",
 *     shortName="CheckinGuestCheckInAction",
 *     description="Location guest check-in action",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"Checkin:output", "Place:output"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"Checkin:input"}
 *     }
 * )
 */
class GuestCheckInAction
{
    /**
     * @Groups({"Checkin:output"})
     * @ApiProperty(identifier=true, iri="http://schema.org/identifier")
     * Note: Every entity needs an identifier!
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/Person")
     * @Groups({"Checkin:output"})
     *
     * @var Person
     */
    private $agent;

    /**
     * @ApiProperty(iri="http://schema.org/location")
     * @Groups({"Checkin:output", "Checkin:input"})
     * @Assert\NotBlank
     *
     * @var Place
     */
    private $location;

    /**
     * @ApiProperty(iri="http://schema.org/seatNumber")
     * @Groups({"Checkin:output", "Checkin:input"})
     *
     * @var ?int
     */
    private $seatNumber;

    /**
     * @ApiProperty(iri="https://schema.org/startTime")
     * @Groups({"Checkin:output"})
     *
     * @var \DateTimeInterface
     */
    private $startTime;

    /**
     * @ApiProperty(iri="https://schema.org/endTime")
     * @Groups({"Checkin:output", "Checkin:input"})
     * @Assert\Type("\DateTimeInterface")
     * @Assert\NotBlank
     *
     * @var \DateTimeInterface
     */
    private $endTime;

    /**
     * @ApiProperty(iri="http://schema.org/email")
     * @Groups({"Checkin:output", "Checkin:input"})
     * @Assert\Email
     * @Assert\NotBlank
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

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
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
