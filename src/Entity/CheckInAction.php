<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CheckinBundle\State\CheckInActionProcessor;
use Dbp\Relay\CheckinBundle\State\CheckInActionProvider;
use Dbp\Relay\CheckinBundle\State\DummyProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'CheckinCheckInAction',
    description: 'Location check-in action',
    types: ['http://schema.org/CheckInAction'],
    operations: [
        new Get(
            uriTemplate: '/checkin/check-in-actions/{identifier}',
            openapi: new Operation(
                tags: ['Checkin']
            ),
            provider: DummyProvider::class
        ),
        new GetCollection(
            uriTemplate: '/checkin/check-in-actions',
            openapi: new Operation(
                summary: 'Retrieves all CheckinCheckInActions of the current user.',
                tags: ['Checkin'],
                parameters: [
                    new Parameter(
                        name: 'location',
                        in: 'query',
                        description: 'Location',
                        schema: ['type' => 'string'],
                        example: 'f0ad66aaaf1debabb44a'
                    ),
                ]
            ),
            normalizationContext: [
                'groups' => ['Checkin:output', 'Checkin:outputList'],
            ],
            provider: CheckInActionProvider::class
        ),
        new Post(
            uriTemplate: '/checkin/check-in-actions',
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
            processor: CheckInActionProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['Checkin:output', 'Place:output'],
        'jsonld_embed_context' => true,
    ],
    denormalizationContext: [
        'groups' => ['Checkin:input'],
    ],
    security: 'is_granted("IS_AUTHENTICATED_FULLY")'
)]
class CheckInAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['Checkin:output'])]
    private $identifier;

    /**
     * @var Person
     */
    #[ApiProperty(iris: ['http://schema.org/Person'])]
    #[Groups(['Checkin:output'])]
    private $agent;

    /**
     * @var Place
     */
    #[ApiProperty(iris: ['http://schema.org/location'])]
    #[Groups(['Checkin:output', 'Checkin:input'])]
    #[Assert\NotBlank]
    private $location;

    /**
     * @var ?int
     */
    #[ApiProperty(iris: ['http://schema.org/seatNumber'])]
    #[Groups(['Checkin:output', 'Checkin:input'])]
    private $seatNumber;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['https://schema.org/startTime'])]
    #[Groups(['Checkin:output'])]
    private $startTime;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['https://schema.org/endTime'])]
    #[Groups(['Checkin:output'])]
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

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }
}
