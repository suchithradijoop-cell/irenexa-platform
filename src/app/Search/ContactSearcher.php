<?php

declare(strict_types=1);

namespace App\Search;

use App\Support\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * "Find contacts of the current tenant matching a phrase, best first."
 *
 * Separate from ContactRepositoryInterface on purpose (Interface Segregation):
 * storing contacts and searching them are different jobs with different
 * technologies behind them (MySQL FULLTEXT or Meilisearch), chosen by
 * SEARCH_DRIVER. The phrase must be non-empty.
 */
interface ContactSearcher
{
    public function search(SearchQuery $query, int $perPage): LengthAwarePaginator;
}
