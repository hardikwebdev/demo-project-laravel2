<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewFeedback extends Model
{
    //
    public $table  = 'review_feedbacks';


    public function service() 
    {
        return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
    }
    public function user() 
    {
        return $this->belongsTo('App\User','user_id','id');
    }
    public function order() 
    {
        return $this->belongsTo('App\Order','order_id','id');
    }
    
}
