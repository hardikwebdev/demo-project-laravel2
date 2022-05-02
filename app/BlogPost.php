<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

    public function category()
    {
        return $this->belongsTo('App\BlogCategory','category_id','id');
    }

    public function category_associate()
    {
        return $this->hasMany('App\BlogCategoryAssociate','blog_id','id');
    }

    public function subcategory()
    {
        return $this->belongsTo('App\BlogCategory','subcategory_id','id');
    }

    public function media_images()
    {
        return $this->hasMany('App\BlogPostMedia', 'post_id', 'id');
    }
    public function tagAssociate(){
        return $this->hasMany('App\BlogTagAssociate','blog_id','id');
    }
}
