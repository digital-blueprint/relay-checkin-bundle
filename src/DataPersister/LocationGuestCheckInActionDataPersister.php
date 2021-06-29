<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\CoreBundle\API\PersonProviderInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Exception\ItemNotUsableException;
use DBP\API\LocationCheckInBundle\Entity\LocationGuestCheckInAction;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LocationGuestCheckInActionDataPersister extends AbstractController implements DataPersisterInterface
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
     * @param LocationGuestCheckInAction $data
     *
     * @return LocationGuestCheckInAction
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws AccessDeniedHttpException
     * @throws ItemNotUsableException
     */
    public function persist($data)
    {
        $locationGuestCheckInAction = $data;
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN-GUEST');

        $location = $locationGuestCheckInAction->getLocation();
        $locationGuestCheckInAction->setIdentifier(md5($location->getIdentifier().rand(0, 10000).time()));
        $locationGuestCheckInAction->setStartTime(new \DateTime());
        $locationGuestCheckInAction->setAgent($this->personProvider->getCurrentPerson());

        $this->api->seatCheck($location, $locationGuestCheckInAction->getSeatNumber());

        // check if endDate is in the past
        if ((new \DateTime()) > $locationGuestCheckInAction->getEndTime()) {
            throw new ItemNotStoredException('The endDate must be in the future!');
        }

        // check if endDate is too far in the future
        $maxCheckInEndTime = $this->api->fetchMaxCheckInEndTime();
        if ($maxCheckInEndTime < $locationGuestCheckInAction->getEndTime()) {
            $maxCheckInEndTimeString = $maxCheckInEndTime->format('c');
            throw new ItemNotStoredException("The endDate can't be after ${maxCheckInEndTimeString}!");
        }

        // We want to wait until we have checked if the guest already took the same seat
        // This lock will be auto-released
        // https://gitlab.tugraz.at/dbp/middleware/api/-/issues/64
        $lock = $this->api->acquireBlockingLock(
            sprintf(
                'guest-check-in-%s-%s-%s',
                $location->getIdentifier(),
                $locationGuestCheckInAction->getSeatNumber(),
                $locationGuestCheckInAction->getEmail()
            )
        );

        try {
            // check if there are check-ins for with guest email
            $existingCheckIns = $this->api->fetchLocationCheckInActionsOfEmail(
                $locationGuestCheckInAction->getEmail(),
                $location->getIdentifier(),
                $locationGuestCheckInAction->getSeatNumber());

            if (count($existingCheckIns) > 0) {
                throw new ItemNotStoredException('There are already check-ins at the location with provided seat for the email address!');
            }

            // send the guest check-in request
            $this->api->sendCampusQRGuestCheckInRequest($locationGuestCheckInAction);
        } finally {
            $lock->release();
        }

        // dispatch guest check out message
        $this->api->createAndDispatchLocationGuestCheckOutMessage($locationGuestCheckInAction);

        return $locationGuestCheckInAction;
    }

    /**
     * @param LocationGuestCheckInAction $data
     */
    public function remove($data)
    {
    }
}
