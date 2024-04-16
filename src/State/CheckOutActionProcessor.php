<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\CheckinBundle\Entity\CheckOutAction;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @psalm-suppress MissingTemplateParam
 */
class CheckOutActionProcessor extends AbstractController implements ProcessorInterface
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
