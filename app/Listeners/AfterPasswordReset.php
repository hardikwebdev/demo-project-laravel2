<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\PasswordReset;
use App\User;
use App\OauthAccessToken;
use App\UserDevice;

class AfterPasswordReset
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PasswordReset $event)
    {
        /* if password change from web, then remove all login devices from app */
        UserDevice::where('user_id',$event->user->id)->delete();
        OauthAccessToken::where('user_id',$event->user->id)->delete();
        $user = User::select('id','total_app_login')->find($event->user->id);
        $user->total_app_login = 0;
        $user->save();
    }
}
