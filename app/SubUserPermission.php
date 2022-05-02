<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class SubUserPermission extends Model {

	public $table = 'sub_user_permissions';

	public function sub_user()
    {
        return $this->hasOne('App\User','id','subuser_id');
    }

}