<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class OrderRevisions extends Model {
    protected $table = 'order_revisions';
    
    public function seller_work() {
		return $this->hasMany('App\SellerWork', 'order_revision_id', 'id');
    }
    
    public function order()
    {
        return $this->belongsTo('App\Order','order_id','id');
    }
}