<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceMedia extends Model
{
    public $table = 'service_media';

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

    public function service() {
        return $this->belongsTo('App\Service', 'service_id', 'id')->withoutGlobalScope('is_course');
      }
  
}
