<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\LocationCheckInBundle\Entity\LocationGuestCheckInAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LocationGuestCheckInActionDataPersister implements DataPersisterInterface
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
        return $data instanceof LocationGuestCheckInAction;
    }

    /**
     * @param LocationGuestCheckInAction $locationGuestCheckInAction
     *
     * @return LocationGuestCheckInAction
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     */
    public function persist($locationGuestCheckInAction)
    {
        $location = $locationGuestCheckInAction->getLocation();
        $locationGuestCheckInAction->setIdentifier(md5($location->getIdentifier() . rand(0, 10000) . time()));
        $locationGuestCheckInAction->setStartTime(new \DateTime());
        $locationGuestCheckInAction->setAgent($this->personProvider->getCurrentPerson());

        $this->api->seatCheck($location, $locationGuestCheckInAction->getSeatNumber());

        if ((new \DateTime()) > $locationGuestCheckInAction->getEndTime()) {
            throw new ItemNotStoredException("The endDate must be in the future!");
        }

        $existingCheckIns = $this->api->fetchLocationCheckInActionsOfCurrentPerson(
            $location->getIdentifier(),
            $locationGuestCheckInAction->getSeatNumber());

        if (count($existingCheckIns) > 0) {
            throw new ItemNotStoredException("There are already check-ins at the location with provided seat for the current user!");
        }

        $this->api->sendCampusQRGuestCheckInRequest($locationGuestCheckInAction);

        // TODO: Write checkout in message queue

        return $locationGuestCheckInAction;
    }

    /**
     * @param LocationGuestCheckInAction $locationGuestCheckInAction
     */
    public function remove($locationGuestCheckInAction)
    {
    }
}
