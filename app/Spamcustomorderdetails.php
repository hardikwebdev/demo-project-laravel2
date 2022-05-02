<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Spamcustomorderdetails extends Model
{
    protected $table = "spam_custom_order_details";

    public function service()
    {
    	return $this->hasOne('App\Service','id','service_id')->where('status', 'custom_order');
    }

    public function fromUser(){
    	return $this->hasOne('App\User','id','from_user');
    }
    public function toUser(){
    	return $this->hasOne('App\User','id','to_user');
    }
}
