<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoostedServicesOrders extends Model
{
    protected $fillable = [
    	'id' ,'uid' ,'service_id' ,'plan_id' ,'txn_id','receipt','start_date' ,'end_date' ,'payment_status','payment_by'
    ];
}
