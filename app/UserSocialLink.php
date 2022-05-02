<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSocialLink extends Model
{

    protected $appends = ['facebook_url','twitter_url','linkedin_url','youtube_url','instagram_url'];
    
    /* Facebook URL*/
    public function getFacebookUrlAttribute()
    {
        $facebook = "";
        if($this->facebook_link != ""){
            $facebook = env('FACEBOOK_BASE_URL').$this->facebook_link;
        }
        return $facebook;
    }

    /* Twitter URL*/
    public function getTwitterUrlAttribute()
    {
        $twitter = "";
        if($this->twitter_link != ""){
            $twitter = env('TWITTER_BASE_URL').$this->twitter_link;
        }
        return $twitter;
    }

    /* Linkedin URL*/
    public function getLinkedinUrlAttribute()
    {
        $linkedin = "";
        if($this->linkedin_link != ""){
            $linkedin = env('LINKEDIN_BASE_URL').$this->linkedin_link;
        }
        return $linkedin;
    }

    /* Youtube URL*/
    public function getYoutubeUrlAttribute()
    {
        $youtube = "";
        if($this->youtube_link != ""){
            $youtube = env('YOUTUBE_BASE_URL').$this->youtube_link;
        }
        return $youtube;
    }

    /* Instagram URL*/
    public function getInstagramUrlAttribute()
    {
        $instagram = "";
        if($this->instagram_link != ""){
            $instagram = env('INSTAGRAM_BASE_URL').$this->instagram_link;
        }
        return $instagram;
    }
}
