<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlogCategoryAssociate extends Model
{
    //
    protected $table = 'blog_category_associates';

    protected $appends = ['secret'];
    
    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }
    
    public function posts() {
		return $this->hasMany('App\BlogPost', 'id', 'blog_id');
	}
    public function category()
    {
        return $this->belongsTo('App\BlogCategory','category_id','id');
    }
}
