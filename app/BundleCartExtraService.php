<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BundleCartExtraService extends Model
{
    public function service_extra()
    {
        return $this->belongsTo('App\ServiceExtra','service_extra_id','id');
    }
}
