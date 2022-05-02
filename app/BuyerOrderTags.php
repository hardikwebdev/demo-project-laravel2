<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BuyerOrderTags extends Model
{

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }

    public static function getEncryptedSecret($id)
    {
        $encrypted_string=openssl_encrypt($id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }

    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

    public function tag_orders(){
		return $this->hasMany('App\BuyerOrderTagDetails','tag_id','id');
	}
}
