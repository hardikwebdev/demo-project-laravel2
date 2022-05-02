<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnsubscribeEmail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','status','email_type'];

    public static function updateStatus($user_id,$email_type,$status){
        $unsubscribe_email = UnsubscribeEmail::where('user_id',$user_id)->where('email_type',$email_type)->first();
        if(!$unsubscribe_email){
            $unsubscribe_email = new UnsubscribeEmail;
        }
        $unsubscribe_email->user_id = $user_id;
        $unsubscribe_email->email_type = $email_type;
        $unsubscribe_email->status = $status;
        $unsubscribe_email->save();
    }
}
