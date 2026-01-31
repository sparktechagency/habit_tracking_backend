<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChallengeCreatedNotification extends Notification
{
    use Queueable;

    private $fromUser;
    private $message;

    public function __construct($fromUser, $message)
    {
        $this->fromUser = $fromUser;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => "New group challenge",
            'user_name' => $this->fromUser,
            'body'  => $this->message,
        ];
    }
}
