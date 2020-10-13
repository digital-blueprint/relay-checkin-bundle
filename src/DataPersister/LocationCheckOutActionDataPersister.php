<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LocationCheckOutActionDataPersister implements DataPersisterInterface
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
        return $data instanceof LocationCheckOutAction;
    }

    /**
     * @param LocationCheckOutAction $locationCheckOutAction
     *
     * @return LocationCheckOutAction
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function persist($locationCheckOutAction)
    {
        $location = $locationCheckOutAction->getLocation();
        $locationCheckOutAction->setIdentifier(md5($location->getIdentifier() . rand(0, 10000) . time()));
        $locationCheckOutAction->setAgent($this->personProvider->getCurrentPerson());

        $seatNumber = $locationCheckOutAction->getSeatNumber();
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
            $locationCheckOutAction->getSeatNumber());

        if (count($existingCheckIns) == 0) {
            throw new ItemNotStoredException("There is no check-ins at the location with provided seat for the current user!");
        }

        $this->api->sendCampusQRCheckOutRequest($locationCheckOutAction);

        return $locationCheckOutAction;
    }

    /**
     * @param LocationCheckOutAction $locationCheckOutAction
     */
    public function remove($locationCheckOutAction)
    {
    }
}
