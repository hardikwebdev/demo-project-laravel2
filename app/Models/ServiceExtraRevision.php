<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceExtraRevision extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['service_id','service_revision_id','title','description','price','delivery_days'];
}
