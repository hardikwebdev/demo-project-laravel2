<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockUser extends Model
{
    public function block_user() 
    {
        return $this->belongsTo('App\User','block_user_id','id');
    }

    public function blockby_user()
    {
        return $this->belongsTo('App\User','block_by','id');
    }

    protected $appends = ['block_user_secret','block_by_secret'];

    public function getBlockUserSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->block_user_id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    
    public function getBlockBySecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->block_by,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }
}
