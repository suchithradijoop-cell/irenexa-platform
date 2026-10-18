<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Support\SearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function all(): Collection
    {
        return Contact::all();
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Contact::paginate($perPage);
    }

    public function search(SearchQuery $query, int $perPage): LengthAwarePaginator
    {
        // Contact::query() carries the TenantScope, so "AND tenant_id = ?"
        // is added automatically: search can only ever see this tenant.
        $builder = Contact::query();

        if (DB::getDriverName() === 'mysql') {
            $match = 'MATCH(name, email, job_title) AGAINST (? IN BOOLEAN MODE)';
            $boolean = $query->toBooleanMode();

            // User text is ONLY ever a bound parameter (the ?), never part of
            // the SQL string. The same MATCH is used twice: to filter, and
            // to sort by relevance score (best match first).
            return $builder
                ->whereRaw($match, [$boolean])
                ->orderByRaw($match.' DESC', [$boolean])
                ->paginate($perPage);
        }

        // Fallback for databases without FULLTEXT (the SQLite test suite).
        // Same meaning, simpler engine: every word must prefix-match one of
        // the searchable columns.
        foreach ($query->words as $word) {
            $builder->where(function (Builder $group) use ($word): void {
                $like = $word.'%';
                $group->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('job_title', 'like', $like)
                    ->orWhere('name', 'like', '% '.$like);
            });
        }

        return $builder->orderBy('name')->paginate($perPage);
    }

    public function find(int $id): ?Contact
    {
        return Contact::find($id);
    }

    public function create(array $data): Contact
    {
        return Contact::create($data);
    }
}
