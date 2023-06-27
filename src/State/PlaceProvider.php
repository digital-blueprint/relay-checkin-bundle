<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CheckinBundle\Entity\Place;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PlaceProvider extends AbstractController implements ProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    /**
     * @var CheckinApi
     */
    private $api;

    public function __construct(CheckinApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return Place|iterable<Place>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        $api = $this->api;

        if ($operation instanceof CollectionOperationInterface) {
            $filters = $context['filters'] ?? [];
            $name = $filters['search'] ?? '';

            $checkInPlaces = $api->fetchPlaces($name);

            $perPage = self::ITEMS_PER_PAGE;
            $page = 1;
            if (isset($context['filters']['page'])) {
                $page = (int) $context['filters']['page'];
            }

            if (isset($context['filters']['perPage'])) {
                $perPage = (int) $context['filters']['perPage'];
            }

            return new ArrayFullPaginator($checkInPlaces, $page, $perPage);
        } else {
            $id = $uriVariables['identifier'];
            assert(is_string($id));

            return $this->api->fetchPlace($id);
        }
    }
}
