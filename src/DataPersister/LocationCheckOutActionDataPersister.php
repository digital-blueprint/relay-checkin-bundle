<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Exception\ItemNotUsableException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckOutAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LocationCheckOutActionDataPersister extends AbstractController implements DataPersisterInterface
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
        return $data instanceof LocationCheckOutAction;
    }

    /**
     * @param LocationCheckOutAction $data
     *
     * @return LocationCheckOutAction
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     * @throws ItemNotUsableException
     */
    public function persist($data)
    {
        $locationCheckOutAction = $data;
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        $person = $this->personProvider->getCurrentPerson();
        $location = $locationCheckOutAction->getLocation();
        $locationCheckOutAction->setIdentifier(md5($location->getIdentifier().rand(0, 10000).time()));
        $locationCheckOutAction->setAgent($person);

        $seatNumber = $locationCheckOutAction->getSeatNumber();
        $maximumPhysicalAttendeeCapacity = $location->getMaximumPhysicalAttendeeCapacity();

        if ($seatNumber === null && $maximumPhysicalAttendeeCapacity !== null) {
            throw new ItemNotStoredException('Location has seats activated, you need to set a seatNumber!');
        } elseif ($seatNumber !== null && $maximumPhysicalAttendeeCapacity === null) {
            throw new ItemNotStoredException("Location doesn't have any seats activated, you cannot set a seatNumber!");
        } elseif ($seatNumber !== null && $seatNumber > $maximumPhysicalAttendeeCapacity) {
            throw new ItemNotStoredException('seatNumber must not exceed maximumPhysicalAttendeeCapacity of location!');
        } elseif ($seatNumber !== null && $seatNumber < 1) {
            throw new ItemNotStoredException('seatNumber too low!');
        }

        // We want to wait until we have checked if the current person really has taken the seat
        // This lock will be auto-released
        // https://gitlab.tugraz.at/dbp/middleware/api/-/issues/64
        $lock = $this->api->acquireBlockingLock(
            sprintf(
                'check-out-%s-%s-%s',
                $location->getIdentifier(),
                $locationCheckOutAction->getSeatNumber(),
                $person->getEmail()
            )
        );
        try {
            $existingCheckIns = $this->api->fetchLocationCheckInActionsOfCurrentPerson(
                $location->getIdentifier(),
                $locationCheckOutAction->getSeatNumber());

            if (count($existingCheckIns) === 0) {
                throw new ItemNotStoredException('There are no check-ins at the location with provided seat for the current user!');
            }

            $this->api->sendCampusQRCheckOutRequestForLocationCheckOutAction($locationCheckOutAction);
        } finally {
            $lock->release();
        }

        return $locationCheckOutAction;
    }

    /**
     * @param LocationCheckOutAction $data
     */
    public function remove($data)
    {
    }
}
