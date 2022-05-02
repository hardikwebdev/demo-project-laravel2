<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSearchTerm extends Model
{
   protected $casts = ['search_term'=>'array'];
}
