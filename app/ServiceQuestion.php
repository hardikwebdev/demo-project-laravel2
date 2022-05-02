<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceQuestion extends Model
{
    protected $fillable = [
    	'id' ,'service_id' ,'question' ,'answer_type' ,'is_required' ,'updated_at' ,'created_at'
    ];
}
