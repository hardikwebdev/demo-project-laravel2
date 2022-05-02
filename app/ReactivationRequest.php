<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReactivationRequest extends Model
{
   public $table  = 'reactivation_request';

   public function user()
   {
    	return $this->belongsTo('App\User','uid','id');	
    }
}
