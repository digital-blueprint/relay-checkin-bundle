<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use Dbp\Relay\CheckinBundle\Authorization\AuthorizationService;
use Dbp\Relay\CheckinBundle\DependencyInjection\Configuration;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;

/**
 * @extends  AbstractDataProvider<Place>
 */
class PlaceProvider extends AbstractDataProvider
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

    protected function getItemById(string $id, array $filters = [], array $options = []): ?Place
    {
        return $this->api->fetchPlace($id);
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return $this->api->fetchPlaces($filters['search'] ?? '');
    }
}
