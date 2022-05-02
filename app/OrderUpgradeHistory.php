<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class OrderUpgradeHistory extends Model {

	public $table = 'order_upgrade_history';

}