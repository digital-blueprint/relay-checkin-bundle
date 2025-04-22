<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CheckinBundle\State\CheckOutActionProcessor;
use Dbp\Relay\CheckinBundle\State\DummyProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'CheckinCheckOutAction',
    description: 'Location check-out action',
    types: ['http://schema.org/CheckOutAction'],
    operations: [
        new Get(
            uriTemplate: '/checkin/check-out-actions/{identifier}',
            openapi: new Operation(
                tags: ['Checkin']
            ),
            provider: DummyProvider::class
        ),
        new GetCollection(
            uriTemplate: '/checkin/check-out-actions',
            openapi: new Operation(
                tags: ['Checkin']
            ),
            provider: DummyProvider::class
        ),
        new Post(
            uriTemplate: '/checkin/check-out-actions',
            openapi: new Operation(
                tags: ['Checkin'],
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => [
                            'schema' => [
                                'type' => 'object',
                                'example' => '{"location": "/checkin/places/f0ad66aaaf1debabb44a", "seatNumber": 17}',
                            ],
                        ],
                    ])
                )
            ),
            processor: CheckOutActionProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['CheckOut:output', 'Place:output'],
        'jsonld_embed_context' => true,
    ],
    denormalizationContext: [
        'groups' => ['CheckOut:input'],
    ],
    security: 'is_granted("IS_AUTHENTICATED_FULLY")'
)]
class CheckOutAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['CheckOut:output'])]
    private $identifier;

    /**
     * @var Person
     */
    #[ApiProperty(iris: ['http://schema.org/Person'])]
    #[Groups(['CheckOut:output'])]
    private $agent;

    /**
     * @var Place
     */
    #[ApiProperty(iris: ['http://schema.org/location'])]
    #[Groups(['CheckOut:output', 'CheckOut:input'])]
    #[Assert\NotBlank]
    private $location;

    /**
     * @var ?int
     */
    #[ApiProperty(iris: ['http://schema.org/seatNumber'])]
    #[Groups(['CheckOut:output', 'CheckOut:input'])]
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
