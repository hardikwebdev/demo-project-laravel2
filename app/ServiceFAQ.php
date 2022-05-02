<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceFAQ extends Model
{
    public $table = 'service_faqs';

    public function service() {
      return $this->belongsTo('App\Service', 'service_id', 'id')->withoutGlobalScope('is_course');
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
