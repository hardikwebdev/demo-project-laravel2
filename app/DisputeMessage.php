<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DisputeMessage extends Model
{
	public function messages_detail(){
		return $this->hasMany('App\DisputeMessageDetail','msg_id','id');
	}

	
}
