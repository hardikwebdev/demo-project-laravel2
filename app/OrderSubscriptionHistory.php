<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class OrderSubscriptionHistory extends Model {

	public $table = 'order_subscription_history';

}