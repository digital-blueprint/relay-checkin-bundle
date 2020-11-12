<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotUsableException;
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
     * @throws ItemNotUsableException
     */
    public function persist($locationCheckInAction)
    {
        $person = $this->personProvider->getCurrentPerson();
        $location = $locationCheckInAction->getLocation();
        $locationCheckInAction->setIdentifier(md5($location->getIdentifier() . rand(0, 10000) . time()));
        $locationCheckInAction->setStartTime(new \DateTime());
        $locationCheckInAction->setEndTime($this->api->fetchMaxCheckInEndTime());
        $locationCheckInAction->setAgent($person);

        $this->api->seatCheck($location, $locationCheckInAction->getSeatNumber());

        // We want to wait until we have checked if the current person already took the same seat
        // This lock will be auto-released
        // https://gitlab.tugraz.at/dbp/middleware/api/-/issues/64
        $lock = $this->api->acquireBlockingLock(
            sprintf(
                "check-in-%s-%s-%s",
                $location->getIdentifier(),
                $locationCheckInAction->getSeatNumber(),
                $person->getEmail()
            )
        );

        $existingCheckIns = $this->api->fetchLocationCheckInActionsOfCurrentPerson(
            $location->getIdentifier(),
            $locationCheckInAction->getSeatNumber());

        if (count($existingCheckIns) > 0) {
            throw new ItemNotStoredException("There are already check-ins at the location with provided seat for the current user!");
        }

        $this->api->sendCampusQRCheckInRequest($locationCheckInAction);
        $lock->release();

        return $locationCheckInAction;
    }

    /**
     * @param LocationCheckInAction $locationCheckInAction
     */
    public function remove($locationCheckInAction)
    {
    }
}
