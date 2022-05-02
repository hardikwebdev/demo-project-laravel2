<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{

    protected $appends = ['secret'];
    public function getSecretAttribute() {
        $encrypted_string = openssl_encrypt($this->id, config('services.encryption.type'), config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }

    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret), config('services.encryption.type'), config('services.encryption.secret'));
    }

    public function course(){
        return $this->belongsTo('App\Service', 'course_id', 'id')->withoutGlobalScope('is_course');
    }

    /* Relationship */
    public function content_medias(){
        /* 
            If Admin view course detail page 
                show without approval and without draft
            else If owner login than
                show without approval
            else
                show approved only 
        */
            
        $uid = get_user_id();
        if(\Request::segment(1) == 'course-details-page'){ 
            return $this->hasMany(ContentMedia::class,'course_content_id','id')->orderBy('short_by','asc');
        }elseif($this->course->uid == $uid){ 
            return $this->hasMany(ContentMedia::class,'course_content_id','id')->where('is_draft',0)->orderBy('short_by','asc');
        }else{
            return $this->hasMany(ContentMedia::class,'course_content_id','id')->where('is_draft',0)->where('is_approve',1)->orderBy('short_by','asc');
        }
	}
}
