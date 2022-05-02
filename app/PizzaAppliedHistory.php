<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PizzaAppliedHistory extends Model
{
    public $table  = 'pizza_applied_history';

    public function demoPage() 
    {
        return $this->belongsTo('App\demoPage','pizza_page_id','id');
    }
}