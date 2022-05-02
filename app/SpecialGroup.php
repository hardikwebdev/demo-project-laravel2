<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class SpecialGroup extends Model
{
	protected $table = 'special_groups';

	public function group_services()
    {
        return $this->hasMany('App\SpecialService','group_id','id');
    }
}
