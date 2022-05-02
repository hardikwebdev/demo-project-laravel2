<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ChatMessenger extends Facade 
{

    protected static function getFacadeAccessor() 
    { 
       return 'ChatMessenger'; 
    }
}