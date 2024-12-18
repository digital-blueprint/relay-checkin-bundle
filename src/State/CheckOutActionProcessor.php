<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use Dbp\Relay\CheckinBundle\Authorization\AuthorizationService;
use Dbp\Relay\CheckinBundle\DependencyInjection\Configuration;
use Dbp\Relay\CheckinBundle\Entity\CheckOutAction;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;

class CheckOutActionProcessor extends AbstractDataProcessor
{
    public function __construct(
        private readonly CheckinApi $api,
        private readonly AuthorizationService $authorizationService)
    {
    }

    protected function isCurrentUserGrantedOperationAccess(int $operation): bool
    {
        return $this->authorizationService->isGrantedRole(Configuration::ROLE_LOCATION_CHECK_IN);
    }

    protected function addItem(mixed $data, array $filters): CheckOutAction
    {
        $locationCheckOutAction = $data;
        assert($locationCheckOutAction instanceof CheckOutAction);

        $person = $this->api->getCurrentPerson();
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

        $lock = $this->api->createLock($person->getLocalDataValue(CheckinApi::EMAIL_LOCAL_DATA_ATTRIBUTE), $location->getIdentifier(), $locationCheckOutAction->getSeatNumber());
        $lock->acquire(true);
        try {
            $existingCheckins = $this->api->fetchCheckInActionsOfCurrentPerson(
                $location->getIdentifier(),
                $locationCheckOutAction->getSeatNumber());

            if (count($existingCheckins) === 0) {
                throw new ItemNotStoredException('There are no check-ins at the location with provided seat for the current user!');
            }
            $lock->refresh();
            $this->api->sendCampusQRCheckOutRequestForCheckOutAction($locationCheckOutAction);
        } finally {
            $lock->release();
        }

        return $locationCheckOutAction;
    }
}
