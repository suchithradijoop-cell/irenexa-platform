<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Contact search driver (ADR 010)
    |--------------------------------------------------------------------------
    |
    | "mysql"       - FULLTEXT index inside MySQL. No extra service needed.
    | "meilisearch" - separate search engine, kept in sync through the queue.
    |
    */
    'driver' => env('SEARCH_DRIVER', 'mysql'),

    'meilisearch' => [
        'url' => env('MEILISEARCH_URL', 'http://meilisearch:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'contacts_index' => env('MEILISEARCH_CONTACTS_INDEX', 'irenexa_contacts'),
    ],

];
