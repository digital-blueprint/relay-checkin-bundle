<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\CheckinBundle\Entity\GuestCheckInAction;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @psalm-suppress MissingTemplateParam
 */
class GuestCheckInActionProcessor extends AbstractController implements ProcessorInterface
{
    /**
     * @var CheckinApi
     */
    private $api;

    public function __construct(CheckinApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN-GUEST');

        $locationGuestCheckInAction = $data;
        assert($locationGuestCheckInAction instanceof GuestCheckInAction);

        $location = $locationGuestCheckInAction->getLocation();
        $locationGuestCheckInAction->setIdentifier(md5($location->getIdentifier().rand(0, 10000).time()));
        $locationGuestCheckInAction->setStartTime(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $locationGuestCheckInAction->setAgent($this->api->getCurrentPerson());

        $this->api->seatCheck($location, $locationGuestCheckInAction->getSeatNumber());

        // check if endDate is in the past
        if ((new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) > $locationGuestCheckInAction->getEndTime()) {
            throw new ItemNotStoredException('The endDate must be in the future!');
        }

        // check if endDate is too far in the future
        $maxCheckinEndTime = $this->api->fetchMaxCheckinEndTime();
        if ($maxCheckinEndTime < $locationGuestCheckInAction->getEndTime()) {
            $maxCheckinEndTimeString = $maxCheckinEndTime->format('c');
            throw new ItemNotStoredException("The endDate can't be after {$maxCheckinEndTimeString}!");
        }

        $lock = $this->api->createLock($locationGuestCheckInAction->getEmail(), $location->getIdentifier(), $locationGuestCheckInAction->getSeatNumber());
        $lock->acquire(true);
        try {
            // check if there are check-ins for with guest email
            $existingCheckins = $this->api->fetchCheckInActionsOfEmail(
                $locationGuestCheckInAction->getEmail(),
                $location->getIdentifier(),
                $locationGuestCheckInAction->getSeatNumber());

            if (count($existingCheckins) > 0) {
                throw new ItemNotStoredException('There are already check-ins at the location with provided seat for the email address!');
            }

            $lock->refresh();
            // send the guest check-in request
            $this->api->sendCampusQRGuestCheckInRequest($locationGuestCheckInAction);
        } finally {
            $lock->release();
        }

        // dispatch guest check out message
        $this->api->createAndDispatchGuestCheckOutMessage($locationGuestCheckInAction);

        return $locationGuestCheckInAction;
    }
}
