<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaveTemplate extends Model
{
    public $table  = 'save_template';


    public function users() 
    {
        return $this->belongsTo('App\User','seller_uid','id');
    }
}
