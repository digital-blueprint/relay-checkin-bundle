<?php
/**
 * Place item data provider.
 *
 * We need to provide a Place item data provider to be able to post a "location" like
 * "/checkin/places/f0ad66aaaf1debabb44a" in a CheckInAction
 */

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PlaceItemDataProvider extends AbstractController implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    private $requestStack;

    public function __construct(CheckinApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Place::class === $resourceClass;
    }

    /**
     * @param string $resourceClass
     * @param string|null $operationName
     * @param array $context
     *
     * @return Place|null
     *
     * @throws NotFoundHttpException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Place
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        return $this->api->fetchPlace($id);
    }
}
