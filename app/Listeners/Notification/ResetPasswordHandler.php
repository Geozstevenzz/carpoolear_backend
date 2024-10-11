<?php

namespace STS\Listeners\Notification;

use STS\Events\User\Reset;
use Illuminate\Contracts\Queue\ShouldQueue;
use STS\Notifications\ResetPasswordNotification;
use STS\Repository\UserRepository; 

class ResetPasswordHandler implements ShouldQueue
{
    protected $userRepo;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * Handle the event.
     *
     * @param Reset $event
     *
     * @return void
     */
    public function handle(Reset $event)
    {
        \Log::info('ResetPasswordHandler');
        $token = $event->token;
        $user = $this->userRepo->show($event->id);
        if ($user) {
            $notification = new ResetPasswordNotification();
            $notification->setAttribute('token', $token);
            $notification->notify($user);
        }
    }
}
