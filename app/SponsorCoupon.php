<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class SponsorCoupon extends Model
{
	protected $table = 'sponser_coupons';

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
