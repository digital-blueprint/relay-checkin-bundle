<?php
/**
 * CheckInPlace item data provider
 *
 * We need to provide a CheckInPlace item data provider to be able to post a "location" like
 * "/check_in_places/c65200af79517a925d44" in a LocationCheckInAction
 */

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Symfony\Component\HttpFoundation\RequestStack;

final class CheckInPlaceItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
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
        return CheckInPlace::class === $resourceClass;
    }

    /**
     * @param string $resourceClass
     * @param array|int|string $id
     * @param string|null $operationName
     * @param array $context
     * @return CheckInPlace|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?CheckInPlace
    {
        $checkInPlace = new CheckInPlace();
        $checkInPlace->setIdentifier($id);

        return $checkInPlace;
    }
}
