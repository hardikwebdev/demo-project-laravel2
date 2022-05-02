<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    public $table  = 'category';
    public $timestamps = false;
    
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

         /* ************************0 - for Services, 1- Courses
		Use without Global scop as below 
		Category::withoutGlobalScope('type')->first(); 
		***************************/
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', 0);
        });

		/* ************************Add New Category for Menu only
		Use without Global scop as below 
		Category::withoutGlobalScope('is_custom')->first(); 
		***************************/
        static::addGlobalScope('is_custom', function (Builder $builder) {
            $builder->where('category.is_custom', 0);
        });
    }

    public function menu_subcategory(){
        return $this->hasMany('App\MenuOrdering','category_id','id')->where(['status'=>1,'menu_status'=>1])->orderBy('display_order', 'desc');
    }

    public function subcategory(){
        return $this->hasMany('App\Subcategory','category_id','id')->where('status',1);
    }
    public function homeService(){
        return $this->hasMany('App\Service','category_id','id')->orderBy('service_rating', 'DESC')->limit(100);
    }
    public function services() {
        return $this->hasMany('App\Service', 'category_id', 'id')
        	->where('status', 'active');
    }
    public function countservices() {
        return $this->hasMany('App\Service', 'category_id', 'id')
            ->where('status', 'active')
            ->where('is_job',0)
            ->where('is_custom_order',0)
            ->where('is_approved',1)
            ->whereHas('user', function ($q) {
                $q->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
            });
    }
    public function jobs() {
        return $this->hasMany('App\Service', 'category_id', 'id')
            ->where('status', 'active')
            ->where('is_job',1)
            ->where('is_custom_order',0)
            ->where('is_approved',1)
            ->whereHas('user', function ($q) {
                $q->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
            })
            ->where(function($query){
                $query->doesntHave('job_accept','or',function($q1){
                    $q1->where('status','accepted');
                });
            });
    }
    
}
