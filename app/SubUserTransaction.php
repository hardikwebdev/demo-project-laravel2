<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class SubUserTransaction extends Model {

	public $table = 'sub_user_transactions';

	public function sub_user()
    {
        return $this->hasOne('App\User','id','sub_user_id');
    }

}