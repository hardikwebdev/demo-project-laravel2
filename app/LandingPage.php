<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class LandingPage extends Model
{
	protected $table = 'landing_pages';

	public function landing_page_sections()
    {
        return $this->hasMany('App\LandingPageSection','landing_page_id','id');
    }

    public function landing_page_active_sections()
    {
        return $this->hasMany('App\LandingPageSection','landing_page_id','id')->where('status',1);
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
    public static function createSecretManually($id)
    {
        $encrypted_string=openssl_encrypt($id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
}
