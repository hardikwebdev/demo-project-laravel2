<?php
namespace App\Commands;
use Request;
trait SortableTrait {
    public function scopeSortable($query) {
        if(Request::input('s') && Request::input('o'))
            return $query->orderBy(Request::input('s'), Request::input('o'));
        else
            return $query;
    }
 
    public static function link_to_sorting_action($col, $title = null) {
        if (is_null($title)) {
            $title = str_replace('_', ' ', $col);
            $title = ucfirst($title);
        }
 
        $indicator = (Request::input('s') == $col ? (Request::input('o') === 'asc' ? '&uarr;' : '&darr;') : null);
        $parameters = array_merge(
            Request::input(), 
            array(
                's' => $col, 
                'o' => Request::input('o') === 'asc' ? 'desc' : 'asc'
                )
            );
 
        return link_to_route(\Route::currentRouteName(), "$title $indicator", $parameters);
    }
}