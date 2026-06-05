<?php

namespace App\Notifications;

use App\Models\SharedAccountInvite as SharedAccountInviteModel;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SharedAccountInvite extends Notification
{
    protected $invite;

    public function __construct(SharedAccountInviteModel $invite)
    {
        $this->invite = $invite;
    }

    public function via($notifiable): array
    {
        return ['mail']; // For your FYP we only use email
    }

    public function toMail($notifiable)
    {
        $code = $this->invite->auth_code;   // <-- match migration
        $email = $this->invite->recipient_contact;

        return (new MailMessage)
            ->subject('🔐 Fundlink Shared Account Verification Code')
            ->greeting("Hello!")
            ->line("You have been invited to join a Shared Account on Fundlink.")
            ->line("Use the following 6-digit code to verify your access:")
            ->line("## **{$code}**")
            ->line("This code expires in 10 minutes.")
            ->salutation("Thank you for using Fundlink!");
    }
}
