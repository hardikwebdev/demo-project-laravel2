<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DisputeOrder extends Model
{

	public function user(){
		return $this->belongsTo('App\User','user_id','id');
	}

	public function reasonData(){
		return $this->belongsTo('App\DisputeReason','reason','id');
	}

	public function orderData(){
		return $this->belongsTo('App\Order','order_no','order_no');
	}

	public function messages(){
		return $this->belongsTo('App\DisputeMessage','id','dispute_id');
	}

	public function userAdmin(){
		return $this->belongsTo('App\Admin','user_id','id');
	}

	protected $appends = ['secret'];

	public function getSecretAttribute()
	{
		$encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
		return base64_encode($encrypted_string);
	}
	public static function getDecryptedId($secret){
		return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
	}
	
}
