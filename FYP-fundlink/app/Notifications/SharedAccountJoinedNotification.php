<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SharedAccountJoinedNotification extends Notification
{
    private string $accountName;
    private string $memberName;
    private int $accountId;

    public function __construct(string $accountName, string $memberName, int $accountId)
    {
        $this->accountName = $accountName;
        $this->memberName = $memberName;
        $this->accountId = $accountId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'message' => "{$this->memberName} joined your shared account: {$this->accountName}.",
            'scope' => 'shared',
            'account_id' => $this->accountId,
        ]);
    }
}
