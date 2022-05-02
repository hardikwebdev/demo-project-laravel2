<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BundleDiscount extends Model
{
    
    public function bundle_services(){
		return $this->hasMany('App\BundleService','bundle_id','id');
	}
}
