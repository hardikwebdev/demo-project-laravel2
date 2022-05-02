<?php

namespace App\Observers;

use App\ServiceExtra;
use App\TrackServiceChange;

class ServiceExtraObserver
{
    /**
     * Handle the service extra "created" event.
     *
     * @param  \App\ServiceExtra  $serviceExtra
     * @return void
     */
    public function created(ServiceExtra $serviceExtra)
    {
        $session_data = \Session::get('_ServiceExtra_Old');
        $count = 0;
        foreach ($session_data as $key => $value) {
            if($value->title == $serviceExtra->title && $value->price == $serviceExtra->price && $value->delivery_days == $serviceExtra->delivery_days) {
                $count++;
            }
        }
        if($count == 0) {
            $tracker = new TrackServiceChange;
            $tracker->service_id = $serviceExtra->service_id;
            $tracker->column_key = 'Service Extra - title';
            $tracker->new_value = $serviceExtra->title;
            $tracker->extra_note = "Added Service Extra - Price : ".$serviceExtra->price . " & Delivery days : ".$serviceExtra->delivery_days;
            $tracker->save();
        }
    }

    /**
     * Handle the service extra "updated" event.
     *
     * @param  \App\ServiceExtra  $serviceExtra
     * @return void
     */
    public function updated(ServiceExtra $serviceExtra)
    {
        //
    }

    /**
     * Handle the service extra "deleted" event.
     *
     * @param  \App\ServiceExtra  $serviceExtra
     * @return void
     */
    public function deleted(ServiceExtra $serviceExtra)
    {
        $session_data = \Session::get('_ServiceExtra_New');
        $count = 0;
        foreach ($session_data as $key => $value) {
            if($value['title'] == $serviceExtra->title && $value['price'] == $serviceExtra->price && $value['delivery_days'] == $serviceExtra->delivery_days) {
                $count++;
            }
        }
        if($count == 0) {
            $tracker = new TrackServiceChange;
            $tracker->service_id = $serviceExtra->service_id;
            $tracker->column_key = 'Service Extra - title';
            $tracker->new_value = $serviceExtra->title;
            $tracker->extra_note = "Deleted Service Extra - Price : ".$serviceExtra->price . " & Delivery days : ".$serviceExtra->delivery_days;
            $tracker->save();
        }
    }

    /**
     * Handle the service extra "restored" event.
     *
     * @param  \App\ServiceExtra  $serviceExtra
     * @return void
     */
    public function restored(ServiceExtra $serviceExtra)
    {
        //
    }

    /**
     * Handle the service extra "force deleted" event.
     *
     * @param  \App\ServiceExtra  $serviceExtra
     * @return void
     */
    public function forceDeleted(ServiceExtra $serviceExtra)
    {
        //
    }
}
