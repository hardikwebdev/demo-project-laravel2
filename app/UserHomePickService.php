<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserHomePickService extends Model
{
   protected $casts = ['service_ids'=>'array','service_ids_for_deal'=>'array'];
}
