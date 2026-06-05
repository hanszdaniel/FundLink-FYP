<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetOverLimitNotification extends Notification
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
            'title' => 'Budget Over Limit',
            'message' => "You have exceeded the budget for {$this->accountName}{$percentText}.",
            'type' => 'over',
            'account_name' => $this->accountName,
            'percent' => $this->percent,
            'scope' => $this->scope,
            'account_id' => $this->accountId,
        ];
    }
}
