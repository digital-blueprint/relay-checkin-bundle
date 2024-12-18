<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\State;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;

/**
 * For GET endpoints which we don't implement, either return an empty collection
 * or return null which gets translated to 404.
 *
 * @extends AbstractDataProvider<object>
 */
class DummyProvider extends AbstractDataProvider
{
    protected function getItemById(string $id, array $filters = [], array $options = []): ?object
    {
        return null;
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return [];
    }
}
