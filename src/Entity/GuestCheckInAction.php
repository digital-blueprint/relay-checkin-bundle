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
use Dbp\Relay\CheckinBundle\State\DummyProvider;
use Dbp\Relay\CheckinBundle\State\GuestCheckInActionProcessor;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'CheckinGuestCheckInAction',
    description: 'Location guest check-in action',
    types: ['http://schema.org/CheckInAction'],
    operations: [
        new Get(
            uriTemplate: '/checkin/guest-check-in-actions/{identifier}',
            openapi: new Operation(
                tags: ['Checkin']
            ),
            provider: DummyProvider::class
        ),
        new GetCollection(
            uriTemplate: '/checkin/guest-check-in-actions',
            openapi: new Operation(
                tags: ['Checkin']
            ),
            provider: DummyProvider::class
        ),
        new Post(
            uriTemplate: '/checkin/guest-check-in-actions',
            openapi: new Operation(
                tags: ['Checkin'],
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => [
                            'schema' => [
                                'type' => 'object',
                                'example' => '{"location": "/checkin/places/f0ad66aaaf1debabb44a", "seatNumber": 17, "email": "test@test.com", "endTime": "2021-10-19T08:03:11.336Z"}',
                            ],
                        ],
                    ])
                )
            ),
            processor: GuestCheckInActionProcessor::class
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
class GuestCheckInAction
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
    #[Groups(['Checkin:output', 'Checkin:input'])]
    #[Assert\Type('\DateTimeInterface')]
    #[Assert\NotBlank]
    private $endTime;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/email'])]
    #[Groups(['Checkin:output', 'Checkin:input'])]
    #[Assert\Email]
    #[Assert\NotBlank]
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
