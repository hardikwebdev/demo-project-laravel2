<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    public $table  = 'subcategory';
    public $timestamps = false;
    public function category(){
        return $this->belongsTo('App\Category','category_id','id')->withoutGlobalScope('type');
    }
}
