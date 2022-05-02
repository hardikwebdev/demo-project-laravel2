<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BundleService extends Model
{
    public function service()
	{
		return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
	}

	public function getBuddleDiscount()
	{
		return $this->belongsTo('App\BundleDiscount','bundle_id','id');
	}
}
