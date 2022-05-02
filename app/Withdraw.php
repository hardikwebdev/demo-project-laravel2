<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    public $table  = 'withdraw_request';

    public function user() 
    {
        return $this->belongsTo('App\User','uid','id');
    }
}
