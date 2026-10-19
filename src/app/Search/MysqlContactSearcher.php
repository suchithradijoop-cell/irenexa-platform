<?php

declare(strict_types=1);

namespace App\Search;

use App\Models\Contact;
use App\Support\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MysqlContactSearcher implements ContactSearcher
{
    public function search(SearchQuery $query, int $perPage): LengthAwarePaginator
    {
        // Contact::query() carries the TenantScope, so "AND tenant_id = ?"
        // is added automatically: search can only ever see this tenant.
        $builder = Contact::query();

        if (DB::getDriverName() === 'mysql') {
            $match = 'MATCH(name, email, job_title) AGAINST (? IN BOOLEAN MODE)';
            $boolean = $query->toBooleanMode();

            // User text is ONLY ever a bound parameter (the ?), never part of
            // the SQL string. The same MATCH filters and sorts by relevance.
            return $builder
                ->whereRaw($match, [$boolean])
                ->orderByRaw($match.' DESC', [$boolean])
                ->paginate($perPage);
        }

        // Fallback for databases without FULLTEXT (the SQLite test suite).
        // FULLTEXT matches the START OF ANY WORD, and words are separated by
        // spaces, '@' and '.', so the fallback checks those same word starts.
        foreach ($query->words as $word) {
            $builder->where(function (Builder $group) use ($word): void {
                $like = $word.'%';
                $group->where('name', 'like', $like)
                    ->orWhere('name', 'like', '% '.$like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('email', 'like', '%@'.$like)
                    ->orWhere('email', 'like', '%.'.$like)
                    ->orWhere('job_title', 'like', $like)
                    ->orWhere('job_title', 'like', '% '.$like);
            });
        }

        return $builder->orderBy('name')->paginate($perPage);
    }
}
