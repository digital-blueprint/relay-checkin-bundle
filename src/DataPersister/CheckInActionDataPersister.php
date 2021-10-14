<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Exceptions\ItemNotStoredException;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\BaseBundle\API\PersonProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CheckInActionDataPersister extends AbstractController implements DataPersisterInterface
{
    private $api;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    public function __construct(CheckinApi $api, PersonProviderInterface $personProvider)
    {
        $this->api = $api;
        $this->personProvider = $personProvider;
    }

    public function supports($data): bool
    {
        return $data instanceof CheckInAction;
    }

    /**
     * @param CheckInAction $data
     *
     * @return CheckInAction
     */
    public function persist($data)
    {
        $locationCheckInAction = $data;
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        $person = $this->personProvider->getCurrentPerson();
        $location = $locationCheckInAction->getLocation();
        $locationCheckInAction->setIdentifier(md5($location->getIdentifier().rand(0, 10000).time()));
        $locationCheckInAction->setStartTime(new \DateTime());
        $locationCheckInAction->setEndTime($this->api->fetchMaxCheckinEndTime());
        $locationCheckInAction->setAgent($person);

        $this->api->seatCheck($location, $locationCheckInAction->getSeatNumber());

        // We want to wait until we have checked if the current person already took the same seat
        // This lock will be auto-released
        // https://gitlab.tugraz.at/dbp/middleware/api/-/issues/64
        $lock = $this->api->acquireBlockingLock(
            sprintf(
                'check-in-%s-%s-%s',
                $location->getIdentifier(),
                $locationCheckInAction->getSeatNumber(),
                $person->getEmail()
            )
        );

        try {
            $existingCheckins = $this->api->fetchCheckInActionsOfCurrentPerson(
                $location->getIdentifier(),
                $locationCheckInAction->getSeatNumber());

            if (count($existingCheckins) > 0) {
                throw new ItemNotStoredException('There are already check-ins at the location with provided seat for the current user!');
            }

            $this->api->sendCampusQRCheckInRequest($locationCheckInAction);
        } finally {
            $lock->release();
        }

        return $locationCheckInAction;
    }

    /**
     * @param CheckInAction $data
     */
    public function remove($data)
    {
    }
}
