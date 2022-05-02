<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrackOrderChange extends Model
{
    public function order(){
        return $this->hasOne('App\Order','order_id','id');
    }
    public function user(){
        return $this->hasOne('App\User','id','updated_by');
    }
    public function admin(){
        return $this->hasOne('App\Models\Admin','id','updated_by');
    }
}
