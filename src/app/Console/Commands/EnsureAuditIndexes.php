<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

/**
 * Creates the indexes the audit collection needs. Safe to run repeatedly
 * (MongoDB ignores an index that already exists), so it can run on every
 * deploy.
 *
 * Why a command and not a migration: migrations run for every environment,
 * including the test suite, which has no MongoDB. Index management for the
 * document store is an explicit operational step instead.
 */
class EnsureAuditIndexes extends Command
{
    protected $signature = 'audit:ensure-indexes';

    protected $description = 'Create the MongoDB indexes used by the audit stream';

    public function handle(): int
    {
        Schema::connection('mongodb')->table('audit_entries', function (Blueprint $collection) {
            // Every query is tenant-scoped and wants newest-first, so this
            // compound index (tenant, then newest) serves the main timeline
            // query and the tenant filter in one lookup. Without it, MongoDB
            // would scan the whole collection for every request.
            $collection->index(['tenant_id' => 1, 'created_at' => -1], 'tenant_timeline');
        });

        $this->info('Audit indexes are in place.');

        return self::SUCCESS;
    }
}
