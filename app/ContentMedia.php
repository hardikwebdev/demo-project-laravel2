<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContentMedia extends Model
{
    protected $appends = ['secret'];
    public function getSecretAttribute() {
        $encrypted_string = openssl_encrypt($this->id, config('services.encryption.type'), config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }

    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret), config('services.encryption.type'), config('services.encryption.secret'));
    }

    /* Relationship */
    public function course(){
        return $this->belongsTo('App\Service', 'course_id', 'id')->withoutGlobalScope('is_course');
    }

    public function section(){
        return $this->belongsTo('App\CourseSection', 'course_content_id', 'id');
    }

    public function downloadable_resources(){
        //If owner login than
            //show without approval
        //else
            // show approved only
        $uid = get_user_id();
        if($this->course->uid == $uid){
            return $this->hasMany(DownloadableContent::class, 'content_media_id', 'id')->where('is_draft',0)->orderBy('short_by','asc');
        }else{
            return $this->hasMany(DownloadableContent::class, 'content_media_id', 'id')->where('is_draft',0)->where('is_approve',1)->orderBy('short_by','asc');
        }
    }

    public static function next_preview_content($course_id,$content_media_id){
		$content_media = ContentMedia::select('id','course_content_id')->where('course_id',$course->id)->where('id',$content_media_id)->first();
        if(is_null($content_media)){
            return null;
        }
        $last_content_media = ContentMedia::select('id')->where('course_id',$course->id)->where('course_content_id',$content_media->course_content_id)->orderBy('short_by','DESC')->first();
        if($content_media->id == $last_content_media->id){
            $active_course_section = CourseSection::select('short_by')->where('course_id',$course->id)->where('id',$content_media->course_content_id)->first();
            if(is_null($active_course_section)){
                return null;
            }
            $course_section = CourseSection::select('id')->where('course_id',$course->id)->where('short_by',$active_course_section->short_by+1)->first();
            if(is_null($course_section)){
                return null;
            }
		    $next_content_media = ContentMedia::where('course_id',$course->id)->where('course_content_id',$course_section->id)->orderBy('short_by','ASC')->first();
        }else{
		    $next_content_media = ContentMedia::select('id','course_content_id')->where('course_id',$course->id)->where('short_by',$content_media->short_by+1)->first();
        }
        return $next_content_media;
    }
}
