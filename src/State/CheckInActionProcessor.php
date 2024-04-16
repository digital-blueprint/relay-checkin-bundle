<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @psalm-suppress MissingTemplateParam
 */
class CheckInActionProcessor extends AbstractController implements ProcessorInterface
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
