<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendInviteNotification extends Notification
{
    use Queueable;

    private $fromUser;
    private $message;

    private $data;

    public function __construct($fromUser, $message, $data)
    {
        $this->fromUser = $fromUser;
        $this->message = $message;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => "Invitation letter",
            'user_name' => $this->fromUser,
            'body'  => $this->message,
            'data' => $this->data,
            'invited' => true,
        ];
    }
}
