<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;


class UserPromotionalFundTransaction extends Model
{
	protected $table = 'user_promotional_fund_transactions';

	public function user() {
		return $this->belongsTo('App\User', 'user_id', 'id');
	}

	public function order()
    {
    	return $this->belongsTo('App\Order','order_id','id');	
	}
	
	public function boost_order()
    {
    	return $this->belongsTo('App\BoostedServicesOrder','boost_order_id','id');	
    }
}
