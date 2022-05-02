<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LearnCourseContent extends Model
{
    protected $fillable = [ 'id', 'user_id', 'course_id', 'content_media_id', 'duration', 'status', 'completed_status'];
    /* Update learn course content completed status */
    public static function update_learn_course_content($uid,$course_id,$content_media_id){
        $learn_content = LearnCourseContent::where('user_id',$uid)->where('course_id',$course_id)->where('content_media_id',$content_media_id)->first();
		if(is_null($learn_content)){
			$learn_content = new LearnCourseContent;
			$learn_content->user_id = $uid;
			$learn_content->course_id = $course_id;
			$learn_content->content_media_id = $content_media_id;
			$learn_content->completed_status = 1;
            $learn_content->duration = 0;
		}elseif($learn_content->completed_status == 0){
            $learn_content->completed_status = 1;
            $learn_content->duration = 0;
        }else{
            $learn_content->completed_status = 0;
        }
        $learn_content->save();
        return $learn_content;
    }

    /* Update active content status */
    public static function update_active_content_status($uid,$course_id,$content_media_id){
        $learn_content = LearnCourseContent::where('user_id',$uid)->where('course_id',$course_id)->where('content_media_id',$content_media_id)->first();
		if(is_null($learn_content)){
            $learn_content = new LearnCourseContent;
			$learn_content->user_id = $uid;
			$learn_content->course_id = $course_id;
			$learn_content->content_media_id = $content_media_id;
        }
        $learn_content->status = 1;
        $learn_content->save();

        LearnCourseContent::where('user_id',$uid)->where('course_id',$course_id)->where('content_media_id','!=',$content_media_id)->where('status',1)->update(['status'=>0]);
        return $learn_content;
    }
}
