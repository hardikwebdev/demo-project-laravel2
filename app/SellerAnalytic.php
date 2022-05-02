<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellerAnalytic extends Model
{
    public function service()
	{
		return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
	}
}
