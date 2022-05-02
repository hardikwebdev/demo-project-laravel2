<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SearchFeedback extends Model
{
    protected $table = 'search_feedbacks';
    
    public function user(){
        return $this->belongsTo('App\User','user_id','id');
    }
    public function category()
    {
        return $this->belongsTo('App\Category','categoryid','id');
    }

    public function subcategory()
    {
        return $this->belongsTo('App\Subcategory','subcategoryid','id');
    }
}
