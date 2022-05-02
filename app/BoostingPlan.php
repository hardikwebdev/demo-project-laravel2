<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoostingPlan extends Model
{
    protected $fillable = [
    	'id' , 'name' , 'description' , 'price' ,'days' ,'created_at' ,'updated_at'
    ];

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
