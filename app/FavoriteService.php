<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FavoriteService extends Model
{
    public $table  = 'favorite_services';

    public function favoriteservice()
    {
    	return $this->hasOne("App\Service",'id','service_id')->where('status','active')->withoutGlobalScope('is_course');
    }
}
