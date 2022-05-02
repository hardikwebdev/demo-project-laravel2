<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Commands\SortableTrait;
use Auth;

class UserLoginByAdminHistory extends Model {

	public $table = 'user_login_by_admin_history';

}