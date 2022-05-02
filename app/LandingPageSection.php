<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class LandingPageSection extends Model
{
	protected $table = 'landing_page_sections';

	public function landing_page_section_services()
    {
        return $this->hasMany('App\LandingPageSectionService','section_id','id');
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
