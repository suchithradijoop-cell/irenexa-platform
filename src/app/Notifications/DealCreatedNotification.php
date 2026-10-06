<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a tenant admin that a new Deal exists. Holds plain values, not
 * the Deal model: the data is stored as JSON in the notifications table,
 * and a snapshot of "what the deal looked like then" is exactly what an
 * inbox entry should be.
 */
class DealCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Each recipient and channel becomes its own queued job, so one slow
     * or failing mail delivery is retried alone — it never re-sends
     * anyone else's notification.
     */
    public int $tries = 3;

    public function __construct(
        protected int $dealId,
        protected string $dealTitle,
        protected float $dealAmount,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    /**
     * Channels this notification is delivered through, per recipient.
     * The in-app inbox is always written; email respects the user's
     * notify_by_email preference.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notify_by_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Content of the email (the "mail" channel). Laravel renders it with
     * a built-in responsive template; the recipient address comes from
     * the notifiable's email attribute automatically.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New deal created: '.$this->dealTitle)
            ->greeting('Hello,')
            ->line('A new deal was created in your IRENEXA workspace.')
            ->line('Deal: '.$this->dealTitle)
            ->line('Amount: '.number_format($this->dealAmount, 2));
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
