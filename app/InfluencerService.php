<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InfluencerService extends Model
{
    public function service() {
		return $this->belongsTo('App\Service', 'service_id', 'id');
	}
}
