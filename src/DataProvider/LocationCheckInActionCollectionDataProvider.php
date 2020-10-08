<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\LocationCheckInBundle\Entity\LocationCheckInAction;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;

final class LocationCheckInActionCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(LocationCheckInApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return LocationCheckInAction::class === $resourceClass;
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $api = $this->api;
        $filters = $context['filters'] ?? [];
        $location = $filters['location'] ?? '';

        $locationCheckInActions = $api->fetchLocationCheckInActionsOfCurrentPerson($location);

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
