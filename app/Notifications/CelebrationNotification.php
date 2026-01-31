<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CelebrationNotification extends Notification
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
            'title' => "You are congratulated.",
            'user_name' => $this->fromUser,
            'body'  => $this->message,
        ];
    }
}
