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
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function persist($locationCheckInAction)
    {
        $location = $locationCheckInAction->getLocation();
        $locationCheckInAction->setIdentifier(md5($location->getIdentifier() . rand(0, 10000) . time()));
        $locationCheckInAction->setStartTime(new \DateTime());
        $locationCheckInAction->setAgent($this->personProvider->getCurrentPerson());

        $seatNumber = $locationCheckInAction->getSeatNumber();
        $maximumPhysicalAttendeeCapacity = $location->getMaximumPhysicalAttendeeCapacity();

        if ($seatNumber === null && $maximumPhysicalAttendeeCapacity !== null) {
            throw new ItemNotStoredException("Location has seats activated, you need to set a seatNumber!");
        } elseif ($seatNumber !== null && $maximumPhysicalAttendeeCapacity === null) {
            throw new ItemNotStoredException("Location doesn't have any seats activated, you cannot set a seatNumber!");
        } elseif ($seatNumber !== null && $seatNumber > $maximumPhysicalAttendeeCapacity) {
            throw new ItemNotStoredException("seatNumber must not exceed maximumPhysicalAttendeeCapacity of location!");
        } elseif ($seatNumber !== null && $seatNumber < 1) {
            throw new ItemNotStoredException("seatNumber too low!");
        }

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
