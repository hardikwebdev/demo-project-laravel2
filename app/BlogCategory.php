<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';

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
		return $this->hasMany('App\BlogPost', 'category_id', 'id');
	}
    public function BlogCategoryAssociate()
    {
        return $this->belongsTo('App\BlogCategoryAssociate','category_id','id');
    }
    public function children(){
        return $this->hasMany(BlogCategory::class , 'parent_id');
    }
    public function postCount(){
        return $this->hasMany('App\BlogCategoryAssociate' , 'category_id','id');
    }
}
