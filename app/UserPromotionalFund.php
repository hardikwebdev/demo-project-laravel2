<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class UserPromotionalFund extends Model
{
	protected $table = 'user_promotional_funds';

	public function user() {
		return $this->belongsTo('App\User', 'user_id', 'id');
	}
}
