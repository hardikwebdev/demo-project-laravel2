<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingApprovalCount extends Model
{
    protected $fillable = ['services', 'jobs', 'courses', 'users_intro_videos'];

    /*
    * Create Or Update record
    */ 
    public static function updateRecord($requestData){
        $data = PendingApprovalCount::first();
        if($data){
            PendingApprovalCount::where('id',$data->id)->update($requestData); /* update services/jobs count */ 
        }else{
            PendingApprovalCount::create($requestData); /* update services/jobs count */ 
        }
        return true;
    }
}
