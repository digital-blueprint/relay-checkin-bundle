<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use Dbp\Relay\CheckinBundle\Authorization\AuthorizationService;
use Dbp\Relay\CheckinBundle\DependencyInjection\Configuration;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;

class CheckInActionProcessor extends AbstractDataProcessor
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

    protected function addItem(mixed $data, array $filters): CheckInAction
    {
        $locationCheckInAction = $data;
        assert($locationCheckInAction instanceof CheckInAction);

        $person = $this->api->getCurrentPerson();
        $location = $locationCheckInAction->getLocation();
        $locationCheckInAction->setIdentifier(md5($location->getIdentifier().rand(0, 10000).time()));
        $locationCheckInAction->setStartTime(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $locationCheckInAction->setEndTime($this->api->fetchMaxCheckinEndTime());
        $locationCheckInAction->setAgent($person);

        $this->api->seatCheck($location, $locationCheckInAction->getSeatNumber());

        $lock = $this->api->createLock($person->getLocalDataValue(CheckinApi::EMAIL_LOCAL_DATA_ATTRIBUTE), $location->getIdentifier(), $locationCheckInAction->getSeatNumber());
        $lock->acquire(true);
        try {
            $existingCheckins = $this->api->fetchCheckInActionsOfCurrentPerson(
                $location->getIdentifier(),
                $locationCheckInAction->getSeatNumber());

            if (count($existingCheckins) > 0) {
                throw new ItemNotStoredException('There are already check-ins at the location with provided seat for the current user!');
            }
            $lock->refresh();
            $this->api->sendCampusQRCheckInRequest($locationCheckInAction);
        } finally {
            $lock->release();
        }

        return $locationCheckInAction;
    }
}
