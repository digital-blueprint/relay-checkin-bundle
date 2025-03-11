<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use Dbp\Relay\CheckinBundle\Authorization\AuthorizationService;
use Dbp\Relay\CheckinBundle\DependencyInjection\Configuration;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;

/**
 * @extends AbstractDataProvider<CheckInAction>
 */
class CheckInActionProvider extends AbstractDataProvider
{
    public function __construct(
        private readonly CheckinApi $api,
        private readonly AuthorizationService $authorizationService)
    {
        parent::__construct();
    }

    protected function isCurrentUserGrantedOperationAccess(int $operation): bool
    {
        return $this->authorizationService->isGrantedRole(Configuration::ROLE_LOCATION_CHECK_IN);
    }

    protected function getItemById(string $id, array $filters = [], array $options = []): ?object
    {
        throw new \RuntimeException('Unexpected get CheckInAction item request');
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return $this->api->fetchCheckInActionsOfCurrentPerson($filters['location'] ?? '');
    }
}
