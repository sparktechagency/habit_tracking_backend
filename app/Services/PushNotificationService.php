<?php

namespace App\Services;

use Google\Client;
use Google\Service\FirebaseCloudMessaging;

class PushNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(base_path(env('FIREBASE_CREDENTIALS')));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $this->messaging = new FirebaseCloudMessaging($client);
    }
    public function sendNotification($deviceToken, $title, $body, $metadata = [])
    {
        $projectId = env('FIREBASE_PROJECT_ID');

        $message = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => $title,
                    "body"  => $body
                ],
                "data" => !empty($metadata) ? $metadata : null
            ]
        ];

        // return $message;

        return $this->messaging->projects_messages->send(
            "projects/{$projectId}",
            new FirebaseCloudMessaging\SendMessageRequest($message)
        );
    }
}
