<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRevision extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['service_id','title','subtitle','descriptions','category_id','subcategory_id'];
}
