<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Auth;
use App\User;
use Session;
use App\Order;
use App\Service;

class LogSuccessfulLogin
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
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        if(Auth::check()){
            if(!Session::has('login_from_admin')) {
                $updateuser = Auth::user();
                $updateuser->last_login_at = date('Y-m-d H:i:s');
                $updateuser->save();
            }
        }
    }
}
