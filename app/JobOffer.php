<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    public function service()
    {
        return $this->hasOne('App\Service','id','service_id');
    }
    public function user()
    {
    	return $this->belongsTo('App\User','seller_id','id');
    }
    public function buyer()
    {
    	return $this->belongsTo('App\User','buyer_id','id');
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
