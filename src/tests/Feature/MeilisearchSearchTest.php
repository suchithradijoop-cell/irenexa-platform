<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SyncContactToSearchIndexJob;
use App\Models\Contact;
use App\Models\Tenant;
use App\MultiTenancy\TenantContext;
use App\Search\ContactSearcher;
use App\Search\MeilisearchClient;
use App\Search\MeilisearchContactSearcher;
use App\Search\MysqlContactSearcher;
use App\Support\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Meilisearch is faked with Http::fake(): no engine is needed, and every
 * test can assert exactly what we SEND to it, which is where the tenant
 * safety lives.
 */
class MeilisearchSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'search.meilisearch.url' => 'http://meili.test',
            'search.meilisearch.key' => 'test-key',
            'search.meilisearch.contacts_index' => 'contacts_test',
        ]);
    }

    private function contact(Tenant $tenant, string $name): Contact
    {
        return Contact::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'job_title' => null,
        ]);
    }

    // --- driver selection -------------------------------------------------

    public function test_the_driver_setting_selects_the_searcher(): void
    {
        config(['search.driver' => 'mysql']);
        $this->assertInstanceOf(MysqlContactSearcher::class, app(ContactSearcher::class));

        config(['search.driver' => 'meilisearch']);
        $this->assertInstanceOf(MeilisearchContactSearcher::class, app(ContactSearcher::class));
    }

    public function test_an_unknown_driver_fails_loudly(): void
    {
        config(['search.driver' => 'elastic-typo']);

        $this->expectException(InvalidArgumentException::class);

        app(ContactSearcher::class);
    }

    // --- searching --------------------------------------------------------

    public function test_the_query_is_fenced_to_the_current_tenant_and_keeps_the_engines_order(): void
    {
        $tenant = Tenant::factory()->create();
        $first = $this->contact($tenant, 'Best Match');
        $second = $this->contact($tenant, 'Weaker Match');
        app(TenantContext::class)->set($tenant);

        Http::fake(['*' => Http::response([
            // The engine says: second is the better match, then first.
            'hits' => [['id' => $second->id], ['id' => $first->id]],
            'totalHits' => 2,
        ])]);

        $page = app(MeilisearchContactSearcher::class)
            ->search(SearchQuery::fromUserInput('match'), 15);

        $this->assertSame([$second->id, $first->id], collect($page->items())->pluck('id')->all());
        $this->assertSame(2, $page->total());

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/indexes/contacts_test/search')
            && $request['filter'] === 'tenant_id = '.$tenant->id
            && $request['q'] === 'match');
    }

    public function test_an_id_from_another_tenant_in_the_index_is_dropped_by_the_database_layer(): void
    {
        $tenant = Tenant::factory()->create();
        $mine = $this->contact($tenant, 'Mine');
        $foreign = $this->contact(Tenant::factory()->create(), 'Foreign');
        app(TenantContext::class)->set($tenant);

        // Simulate an index bug that leaks a foreign id into the results.
        Http::fake(['*' => Http::response([
            'hits' => [['id' => $foreign->id], ['id' => $mine->id]],
            'totalHits' => 2,
        ])]);

        $page = app(MeilisearchContactSearcher::class)
            ->search(SearchQuery::fromUserInput('anything'), 15);

        $this->assertSame([$mine->id], collect($page->items())->pluck('id')->all());
    }

    public function test_without_a_known_tenant_it_fails_closed_and_never_calls_the_engine(): void
    {
        Http::fake();

        $page = app(MeilisearchContactSearcher::class)
            ->search(SearchQuery::fromUserInput('john'), 15);

        $this->assertSame(0, $page->total());
        Http::assertNothingSent();
    }

    // --- keeping the index in sync ---------------------------------------

    public function test_saving_a_contact_queues_a_sync_job_only_with_the_meilisearch_driver(): void
    {
        $tenant = Tenant::factory()->create();

        Queue::fake();
        config(['search.driver' => 'mysql']);
        $this->contact($tenant, 'No Engine');
        Queue::assertNotPushed(SyncContactToSearchIndexJob::class);

        config(['search.driver' => 'meilisearch']);
        $contact = $this->contact($tenant, 'With Engine');
        Queue::assertPushed(
            SyncContactToSearchIndexJob::class,
            fn (SyncContactToSearchIndexJob $job): bool => $job->tenantId === $tenant->id
                && $job->contactId === $contact->id,
        );
    }

    public function test_the_job_indexes_the_current_row_with_its_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $contact = $this->contact($tenant, 'Indexed Person');
        Http::fake(['*' => Http::response([], 202)]);

        (new SyncContactToSearchIndexJob($tenant->id, $contact->id))
            ->handle(app(TenantContext::class), app(MeilisearchClient::class));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/indexes/contacts_test/documents')
            && $request->data()[0]['id'] === $contact->id
            && $request->data()[0]['tenant_id'] === $tenant->id
            && $request->data()[0]['name'] === 'Indexed Person');
    }

    public function test_the_job_removes_a_contact_that_no_longer_exists(): void
    {
        $tenant = Tenant::factory()->create();
        $contact = $this->contact($tenant, 'Gone Soon');
        $id = $contact->id;
        $contact->delete();
        Http::fake(['*' => Http::response([], 202)]);

        (new SyncContactToSearchIndexJob($tenant->id, $id))
            ->handle(app(TenantContext::class), app(MeilisearchClient::class));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/indexes/contacts_test/documents/'.$id));
    }
}
