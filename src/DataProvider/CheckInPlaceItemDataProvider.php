<?php
/**
 * CheckInPlace item data provider.
 *
 * We need to provide a CheckInPlace item data provider to be able to post a "location" like
 * "/check_in_places/f0ad66aaaf1debabb44a" in a LocationCheckInAction
 */

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\LocationCheckInBundle\Entity\CheckInPlace;
use DBP\API\LocationCheckInBundle\Service\LocationCheckInApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CheckInPlaceItemDataProvider extends AbstractController implements ItemDataProviderInterface, RestrictedDataProviderInterface
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
     *
     * @return CheckInPlace|null
     *
     * @throws ItemNotLoadedException
     * @throws NotFoundHttpException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?CheckInPlace
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        return $this->api->fetchCheckInPlace($id);
    }
}
