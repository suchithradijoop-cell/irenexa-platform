<?php

declare(strict_types=1);

namespace App\Models;

use App\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

/**
 * One entry in the append-only audit/event stream, stored as a MongoDB
 * document (Phase 15, ADR 009).
 *
 * Differences from a MySQL model worth knowing:
 *  - There is no migration and no fixed column list: a document is
 *    whatever fields it is given, so each event type can carry its own
 *    payload shape without a schema change.
 *  - The primary key is a MongoDB ObjectId (a 24-character hex string),
 *    not an auto-increment integer.
 *  - Multi-tenancy works exactly as before: BelongsToTenant adds the
 *    TenantScope filter to every query and stamps tenant_id on create.
 *    Isolation is enforced by us, in the application, the same way as
 *    for MySQL models — MongoDB has no row-level security of its own.
 */
#[Fillable(['event', 'subject_type', 'subject_id', 'data'])]
class AuditEntry extends Model
{
    use BelongsToTenant;

    protected $connection = 'mongodb';

    protected $collection = 'audit_entries';

    // Append-only: entries are written once and never edited.
    public const UPDATED_AT = null;
}
