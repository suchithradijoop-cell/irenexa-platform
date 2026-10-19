<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SyncContactToSearchIndexJob;
use App\Models\Contact;

/**
 * Keeps the search index in step with MySQL. It only dispatches a queued
 * job; the slow HTTP call to the engine never blocks the user's request.
 * With SEARCH_DRIVER=mysql it does nothing, so the default setup and the
 * test suite need no search engine.
 */
class ContactObserver
{
    public function saved(Contact $contact): void
    {
        $this->sync($contact);
    }

    public function deleted(Contact $contact): void
    {
        $this->sync($contact);
    }

    private function sync(Contact $contact): void
    {
        if (config('search.driver') !== 'meilisearch') {
            return;
        }

        SyncContactToSearchIndexJob::dispatch(
            (int) $contact->getAttribute('tenant_id'),
            (int) $contact->getKey(),
        );
    }
}
