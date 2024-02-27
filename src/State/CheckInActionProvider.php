<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CheckinBundle\Entity\CheckInAction;
use Dbp\Relay\CheckinBundle\Service\CheckinApi;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @implements ProviderInterface<CheckInAction>
 */
class CheckInActionProvider extends AbstractController implements ProviderInterface
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
     * @return CheckInAction|iterable<CheckInAction>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SCOPE_LOCATION-CHECK-IN');

        assert($operation instanceof CollectionOperationInterface);

        $api = $this->api;
        $filters = $context['filters'] ?? [];
        $location = $filters['location'] ?? '';

        $locationCheckInActions = $api->fetchCheckInActionsOfCurrentPerson($location);

        return new WholeResultPaginator($locationCheckInActions,
            Pagination::getCurrentPageNumber($filters),
            Pagination::getMaxNumItemsPerPage($filters, self::ITEMS_PER_PAGE));
    }
}
