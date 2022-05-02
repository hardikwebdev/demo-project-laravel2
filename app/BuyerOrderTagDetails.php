<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BuyerOrderTagDetails extends Model
{

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }

    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

    public function tagname() {
		return $this->hasOne('App\BuyerOrderTags', 'id', 'tag_id');
	}
}
