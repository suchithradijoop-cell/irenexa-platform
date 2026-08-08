<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Anything an Activity can be attached to (Contact, Company, Deal) must
 * implement this. Without it, ActivityRepository would have to type-hint
 * the generic Eloquent Model class, which doesn't have an activities()
 * method — PHPStan would (correctly) refuse to let us call it.
 *
 * Deliberately NOT generic here (no "MorphMany<Activity, ...>" in this
 * docblock, just the plain class). Eloquent's morphMany() always returns
 * a MorphMany typed against $this (a "static" type — "this exact class"),
 * and PHPStan's generics are invariant for this template parameter, so a
 * fixed literal class here can never match every implementer's own
 * $this-based return type. Keeping the interface's promise to the plain,
 * non-generic MorphMany class avoids that conflict entirely. Each
 * concrete model (Contact/Company/Deal) still documents its own precise
 * MorphMany<Activity, $this> on its own method — that precision just
 * isn't required by the interface itself.
 */
interface HasActivities
{
    public function activities(): MorphMany;
}
