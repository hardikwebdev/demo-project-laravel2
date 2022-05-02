<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class SpecialService extends Model
{
	protected $table = 'special_services';

	public function service() {
		return $this->belongsTo('App\Service', 'service_id', 'id');
	}
}
