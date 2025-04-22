<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Dbp\Relay\CheckinBundle\State\PlaceProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'CheckinPlace',
    description: 'Check-in place',
    types: ['http://schema.org/Place'],
    operations: [
        new Get(
            uriTemplate: '/checkin/places/{identifier}',
            openapi: new Operation(
                tags: ['Checkin']
            ),
            provider: PlaceProvider::class
        ),
        new GetCollection(
            uriTemplate: '/checkin/places',
            openapi: new Operation(
                tags: ['Checkin'],
                parameters: [
                    new Parameter(
                        name: 'search',
                        in: 'query',
                        description: 'Search for a place name',
                        schema: ['type' => 'string'],
                        example: 'Coworkingspace'
                    ),
                ]
            ),
            provider: PlaceProvider::class
        ),
    ],
    normalizationContext: [
        'groups' => ['Place:output', 'Checkin:outputList'],
        'jsonld_embed_context' => true,
    ],
    denormalizationContext: [
        'groups' => ['Place:input'],
    ],
    security: 'is_granted("IS_AUTHENTICATED_FULLY")'
)]
class Place
{
    #[ApiProperty(identifier: true)]
    #[Groups(['Place:output', 'Checkin:outputList'])]
    private $identifier;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/name'])]
    #[Groups(['Place:output', 'Checkin:outputList'])]
    private $name;

    /**
     * @var ?int
     */
    #[ApiProperty(iris: ['http://schema.org/maximumPhysicalAttendeeCapacity'])]
    #[Groups(['Place:output'])]
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
