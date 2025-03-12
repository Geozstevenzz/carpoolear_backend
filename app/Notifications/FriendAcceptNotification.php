<?php

namespace STS\Notifications;

use  STS\Services\Notifications\BaseNotification;
use  STS\Services\Notifications\Channels\MailChannel;
use  STS\Services\Notifications\Channels\PushChannel;
use  STS\Services\Notifications\Channels\DatabaseChannel;
use  STS\Services\Notifications\Channels\FacebookChannel;

class FriendAcceptNotification extends BaseNotification
{
    protected $via = [
        DatabaseChannel::class, 
        MailChannel::class, 
        PushChannel::class, 
        // FacebookChannel::class
    ];
    
    public function toEmail($user)
    {
        $from = $this->getAttribute('from');
        $senderName = $from ? $from->name : 'Alguien';

        return [
            'title' => $senderName.' ha aceptado tu solicitud de amistad.',
            'email_view' => 'friends_accept',
            'url' => config('app.url').'/app/profile/'.$from->id,
            'name_app' => config('carpoolear.name_app'),
            'domain' => config('app.url')
        ];
    }

    public function toString()
    {
        $from = $this->getAttribute('from');
        $senderName = $from ? $from->name : 'Alguien';
        return $senderName.' ha aceptado tu solicitud de amistad.';
    }

    public function getExtras()
    {
        $from = $this->getAttribute('from');
        return [
            'type' => 'friend',
            'user_id' => $from ? $from->id : null,
        ];
    }

    public function toPush($user, $device)
    {
        $from = $this->getAttribute('from');
        $senderName = $from ? $from->name : 'Alguien';

        return [
            'message' => $senderName.' ha aceptado tu solicitud de amistad.',
            'url' => 'setting/friends',
            'extras' => [
                'id' => $from ? $from->id : null,
            ],
            'image' => 'https://carpoolear.com.ar/app/static/img/carpoolear_logo.png',
        ];
    }
}
