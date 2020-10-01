<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Symfony\Component\HttpFoundation\RequestStack;

final class CheckInLocationItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    private $requestStack;

    public function __construct(LocationCheckInApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return CheckInLocation::class === $resourceClass;
    }

    /**
     * @param array|int|string $id
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?CheckInLocation
    {
        $api = $this->api;

        return $api->getCheckInLocation($id);
    }
}
