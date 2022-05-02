<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpamReport extends Model
{
    public $table = 'spam_reports';
    protected $fillable = [
        'reason'
    ];

    public function fromUser(){
    	return $this->hasOne('App\User','id','from_user');
    }
    public function toUser(){
    	return $this->hasOne('App\User','id','to_user');
    }
}
