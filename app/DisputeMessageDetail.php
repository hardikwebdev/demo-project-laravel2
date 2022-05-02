<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DisputeMessageDetail extends Model
{
	public function admin_user(){
		return $this->belongsTo('App\Models\Admin','from_user','id');
	}
	public function msg_from_user(){
		return $this->belongsTo('App\User','from_user','id');
	}
	public function msg_to_user(){
		return $this->belongsTo('App\User','to_user','id');
	}
	public function messageDetails(){
		return $this->hasOne('App\DisputeMessage','id','msg_id');
	}
}
