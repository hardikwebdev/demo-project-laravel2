<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WelcomeEmail extends Model
{
    protected $table = 'welcome_emails';

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
