<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    public $table  = 'portfolios';

    public function user() 
    {
        return $this->belongsTo('App\User','user_id','id');
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
