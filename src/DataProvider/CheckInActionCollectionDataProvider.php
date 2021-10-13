<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CheckInActionCollectionDataProvider extends AbstractController implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(CheckinApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return CheckInAction::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        $api = $this->api;
        $filters = $context['filters'] ?? [];
        $location = $filters['location'] ?? '';

        $locationCheckInActions = $api->fetchCheckInActionsOfCurrentPerson($location);

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }

        return new ArrayFullPaginator($locationCheckInActions, $page, $perPage);
    }
}
