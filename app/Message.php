<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $table  = 'message';

    public function fromUser() 
    {
        return $this->belongsTo('App\User','from_user','id');
    }
    public function toUser() 
    {
        return $this->belongsTo('App\User','to_user','id');
    }
    public function service()
    {
        return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
    }
    public function order()
    {
        return $this->belongsTo('App\Order','order_id','id');   
    }
    public function messageDetail()
    {
        return $this->hasMany('App\MessageDetail','msg_id','id');   
    }
    public function unreadMessages()
    {
        $uid = auth()->id();
        if(auth()->user()->parent_id != 0){
            $uid = auth()->user()->parent_id;
        }
        return $this->hasMany('App\MessageDetail','msg_id','id')->where('is_read',0)->where('to_user',$uid);   
    }
    public function latestMessage()
    {
        return $this->hasOne('App\MessageDetail','msg_id','id')->latest()/* ->orderBy('created_at','desc') */;   
    }

    public function latestMessageChat()
    {
        $this->uid = auth()->id();
        if(auth()->user()->parent_id != 0){
            $this->uid = auth()->user()->parent_id;
        }
        return $this->hasOne('App\MessageDetail','msg_id','id')->where('to_user',$this->uid)->latest();   
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

    public function fromAdmin() 
    {
        return $this->belongsTo('App\Models\Admin','from_user','id');
    }

    public function getTimeAttribute(){
        $time = $this->updated_at->diffForHumans();
        return $time;
    }
}
