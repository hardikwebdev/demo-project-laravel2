<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellerCategories extends Model
{
	protected $table = 'seller_categories';

	public function category() {
		return $this->belongsTo('App\Category','category_id','id')->withoutGlobalScope('type');
	}

	public function subcategory() {
		return $this->belongsTo('App\Subcategory','sub_category_id','id');
	}
}