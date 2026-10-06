<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Tells a tenant admin that a new Deal exists. Holds plain values, not
 * the Deal model: the data is stored as JSON in the notifications table,
 * and a snapshot of "what the deal looked like then" is exactly what an
 * inbox entry should be.
 */
class DealCreatedNotification extends Notification
{
    public function __construct(
        protected int $dealId,
        protected string $dealTitle,
        protected float $dealAmount,
    ) {}

    /**
     * Channels this notification is delivered through.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Content stored for the in-app inbox (the "database" channel).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'A new deal was created.',
            'deal_id' => $this->dealId,
            'title' => $this->dealTitle,
            'amount' => $this->dealAmount,
        ];
    }
}
