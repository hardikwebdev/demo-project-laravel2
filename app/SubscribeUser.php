<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubscribeUser extends Model
{
    public function subscription() {
		return $this->hasOne('App\Subscription', 'id', 'subscription_id');
	}
	public function seller() {
		return $this->hasOne('App\User', 'id', 'user_id');
	}
}
