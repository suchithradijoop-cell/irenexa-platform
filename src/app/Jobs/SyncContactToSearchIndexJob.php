<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Contact;
use App\MultiTenancy\TenantContext;
use App\Search\MeilisearchClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Copies ONE contact into the search index (or removes it). Carries plain
 * IDs, never the model (Lesson 12.3): the worker sets the tenant itself and
 * then reads the CURRENT row, so a job that runs late still indexes the
 * latest data, and running it twice is harmless (idempotent).
 */
class SyncContactToSearchIndexJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $contactId,
    ) {
        // Dispatch only after the surrounding DB transaction has committed.
        // (Queueable already declares an $afterCommit property, so we call
        // its method instead of redeclaring the property.)
        $this->afterCommit();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(TenantContext $tenantContext, MeilisearchClient $client): void
    {
        $tenantContext->setById($this->tenantId);

        $contact = Contact::find($this->contactId);

        // Deleted since the job was queued: make sure it leaves the index.
        if ($contact === null) {
            $client->deleteDocument($this->contactId);

            return;
        }

        $client->addDocuments([self::document($contact)]);
    }

    /**
     * The shape stored in the index. tenant_id is part of EVERY document:
     * it is what the tenant filter matches on.
     *
     * @return array<string, mixed>
     */
    public static function document(Contact $contact): array
    {
        return [
            'id' => (int) $contact->getKey(),
            'tenant_id' => (int) $contact->getAttribute('tenant_id'),
            'name' => $contact->getAttribute('name'),
            'email' => $contact->getAttribute('email'),
            'job_title' => $contact->getAttribute('job_title'),
        ];
    }
}
