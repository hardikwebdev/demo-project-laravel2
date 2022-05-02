<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFile extends Model
{
    public $table  = 'user_files';

    public function user() 
    {
        return $this->belongsTo('App\User','uid','id');
    }
}
