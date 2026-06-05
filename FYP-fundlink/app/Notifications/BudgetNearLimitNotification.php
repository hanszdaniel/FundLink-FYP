<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetNearLimitNotification extends Notification
{
    use Queueable;

    private string $accountName;
    private ?float $percent;
    private string $scope;
    private $accountId;

    public function __construct(string $accountName = 'Personal Account', ?float $percent = null, string $scope = 'personal', $accountId = null)
    {
        $this->accountName = $accountName;
        $this->percent = $percent;
        $this->scope = $scope;
        $this->accountId = $accountId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $percentText = $this->percent !== null ? " (".round($this->percent, 1)."%)" : '';
        return [
            'title' => 'Budget Near Limit',
            'message' => "You have used more than 75% of the budget for {$this->accountName}{$percentText}.",
            'type' => 'near',
            'account_name' => $this->accountName,
            'percent' => $this->percent,
            'scope' => $this->scope,
            'account_id' => $this->accountId,
        ];
    }
}
