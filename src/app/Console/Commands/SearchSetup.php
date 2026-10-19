<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncContactToSearchIndexJob;
use App\Models\Contact;
use App\Search\MeilisearchClient;
use Illuminate\Console\Command;

class SearchSetup extends Command
{
    protected $signature = 'search:setup {--reindex : Also copy every existing contact into the index}';

    protected $description = 'Configure the Meilisearch contacts index and optionally (re)build it from MySQL';

    public function handle(MeilisearchClient $client): int
    {
        $client->applyContactSettings();
        $this->info('Index settings applied (searchable fields, tenant_id filterable).');

        if (! $this->option('reindex')) {
            return self::SUCCESS;
        }

        // Rebuild from the source of truth. Contacts of ALL tenants are
        // read on purpose here, so the global TenantScope is lifted; every
        // document still carries its own tenant_id.
        $count = 0;
        Contact::withoutGlobalScopes()->chunkById(500, function ($contacts) use ($client, &$count): void {
            $client->addDocuments(
                $contacts->map(fn (Contact $c) => SyncContactToSearchIndexJob::document($c))->all(),
            );
            $count += $contacts->count();
        });

        $this->info("Indexed {$count} contacts.");

        return self::SUCCESS;
    }
}
