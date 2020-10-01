<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
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
     * @throws ItemNotStoredException
     */
    public function persist($locationCheckInAction)
    {
        $location = $locationCheckInAction->getLocation();
        $locationCheckInAction->setIdentifier(md5($location . rand(0, 10000) . time()));
        $locationCheckInAction->setStartTime(new \DateTime());
        $locationCheckInAction->setAgent($this->personProvider->getCurrentPerson());

        try {
            $this->api->sendCampusQRLocationRequest($location);
        } catch (\Exception $e) {
            throw new ItemNotStoredException(sprintf('LocationCheckIn could not be stored: %s', Tools::filterErrorMessage($e->getMessage())));
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
