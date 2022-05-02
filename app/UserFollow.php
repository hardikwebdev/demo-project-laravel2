<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    public $table = 'user_follows';
   
    public const FOLLOW = 1;
    public const UNFOLLOW = 0;

    public const FOLLOWS = [
        self::FOLLOW => 'Follow',
        self::UNFOLLOW => 'Unfollow',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function follower()
    {
        return $this->belongsTo('App\User', 'follower_id', 'id');
    }
}
