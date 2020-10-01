<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use GuzzleHttp\Exception\GuzzleException;
use League\Uri\Contracts\UriException;
use Symfony\Component\HttpFoundation\RequestStack;

final class LocationCheckInActionDataPersister implements DataPersisterInterface
{
    private $api;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    public function __construct(LocationCheckInApi $api,  PersonProviderInterface $personProvider)
    {
        $this->api = $api;
        $this->personProvider = $personProvider;
    }

    public function supports($data): bool
    {
        return $data instanceof LocationCheckInAction;
    }

    /**
     * @param LocationCheckInAction $locationCheckInAction
     *
     * @return LocationCheckInAction
     *
     * @throws \DBP\API\CoreBundle\Exception\ItemNotStoredException
     */
    public function persist($locationCheckInAction)
    {
        $locationCheckInAction->setIdentifier(md5(rand(0, 10000) + time()));
        $locationCheckInAction->setStartTime(new \DateTime());
        $locationCheckInAction->setAgent($this->personProvider->getCurrentPerson());

        $location = $locationCheckInAction->getAgent();

        try {
            $this->api->sendCampusQRLocationRequest($location);
        } catch (\Exception $e) {
            // TODO: throw exception
        }

        return $locationCheckInAction;
    }

    /**
     * @param LocationCheckInAction $authenticImageRequest
     */
    public function remove($authenticImageRequest)
    {
    }
}
