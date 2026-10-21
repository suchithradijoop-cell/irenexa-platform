<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';

    /**
     * Does a tenant in this status keep using the paid plan?
     *
     * PastDue still counts: a failed card is retried by the provider for a
     * few days, and cutting a customer off on the first failure would be
     * both harsh and bad for business. Canceled does not.
     */
    public function grantsAccess(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::PastDue => true,
            self::Canceled => false,
        };
    }
}
