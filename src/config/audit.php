<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Store
    |--------------------------------------------------------------------------
    |
    | Where the append-only audit stream is stored. Supported values:
    |
    |   "mysql"    - the relational audit_logs table (default)
    |   "mongodb"  - the audit_entries collection in MongoDB
    |
    | Application code depends on AuditLogRepositoryInterface and never
    | reads this value; only the container binding does (see
    | RepositoryServiceProvider and ADR 009).
    |
    */

    'store' => env('AUDIT_STORE', 'mysql'),

];
