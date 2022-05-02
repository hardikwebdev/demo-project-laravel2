<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class SubUserChangesHistory extends Model {

	public $table = 'sub_user_changes_history';

	public function sub_user()
    {
        return $this->hasOne('App\User','id','subuser_id');
    }

}