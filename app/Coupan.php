<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupan extends Model
{
    public $table = 'coupans';

    public function service(){
      return $this->hasOne('App\Service','id','service_id');
    }
    public function service_plan(){
      return $this->hasMany('App\ServicePlan','service_id','service_id');
    }
    public function coupan_applied(){
      return $this->hasMany('App\CoupanApplied','coupan_code_id','id');
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
