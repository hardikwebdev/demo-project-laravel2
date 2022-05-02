<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Specialaffiliatedusers extends Model
{
    public $table  = 'special_affiliated_users';
    public $timestamps = false;
    
    public function user(){
        return $this->belongsTo('App\User','uid','id');
    }
}
