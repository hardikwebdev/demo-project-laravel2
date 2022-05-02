<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class LandingPageSectionService extends Model
{
	protected $table = 'landing_page_section_services';

	public function service() {
		return $this->belongsTo('App\Service', 'service_id', 'id');
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
