<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class TempOrder extends Model {

	public $table = 'temp_orders';

	public function extra() {
		return $this->hasMany('App\TempOrderExtra', 'order_id', 'id');
	}

}