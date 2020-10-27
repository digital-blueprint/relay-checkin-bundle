<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LocationCheckInActionDataPersister implements DataPersisterInterface
{
    private $api;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    public function __construct(LocationCheckInApi $api, PersonProviderInterface $personProvider)
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
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function persist($locationCheckInAction)
    {
        $location = $locationCheckInAction->getLocation();
        $locationCheckInAction->setIdentifier(md5($location->getIdentifier() . rand(0, 10000) . time()));
        $locationCheckInAction->setStartTime(new \DateTime());
        $locationCheckInAction->setEndTime($this->api->fetchMaxCheckInEndTime());
        $locationCheckInAction->setAgent($this->personProvider->getCurrentPerson());

        $this->api->seatCheck($location, $locationCheckInAction->getSeatNumber());

        $existingCheckIns = $this->api->fetchLocationCheckInActionsOfCurrentPerson(
            $location->getIdentifier(),
            $locationCheckInAction->getSeatNumber());

        if (count($existingCheckIns) > 0) {
            throw new ItemNotStoredException("There are already check-ins at the location with provided seat for the current user!");
        }

        $this->api->sendCampusQRCheckInRequest($locationCheckInAction);

        return $locationCheckInAction;
    }

    /**
     * @param LocationCheckInAction $locationCheckInAction
     */
    public function remove($locationCheckInAction)
    {
    }
}
