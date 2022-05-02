<?php

namespace App\Observers;

use App\ServiceMedia;
use App\TrackServiceChange;

class ServiceMediaObserver
{
    /**
     * Handle the service media "created" event.
     *
     * @param  \App\ServiceMedia  $serviceMedia
     * @return void
     */
    public function created(ServiceMedia $serviceMedia)
    {
        $tracker = new TrackServiceChange;
        $tracker->service_id = $serviceMedia->service_id;
        $tracker->column_key = 'media_url';
        $tracker->new_value = $serviceMedia->media_url;
        $tracker->extra_note = "Added Media type : ".$serviceMedia->media_type;
        $tracker->save();
    }

    /**
     * Handle the service media "updated" event.
     *
     * @param  \App\ServiceMedia  $serviceMedia
     * @return void
     */
    public function updated(ServiceMedia $serviceMedia)
    {
        //
    }

    /**
     * Handle the service media "deleted" event.
     *
     * @param  \App\ServiceMedia  $serviceMedia
     * @return void
     */
    public function deleted(ServiceMedia $serviceMedia)
    {
        $tracker = new TrackServiceChange;
        $tracker->service_id = $serviceMedia->service_id;
        $tracker->column_key = 'media_url';
        $tracker->new_value = $serviceMedia->media_url;
        $tracker->extra_note = "Deleted Media type : ".$serviceMedia->media_type;
        $tracker->save();
    }

    /**
     * Handle the service media "restored" event.
     *
     * @param  \App\ServiceMedia  $serviceMedia
     * @return void
     */
    public function restored(ServiceMedia $serviceMedia)
    {
        //
    }

    /**
     * Handle the service media "force deleted" event.
     *
     * @param  \App\ServiceMedia  $serviceMedia
     * @return void
     */
    public function forceDeleted(ServiceMedia $serviceMedia)
    {
        //
    }
}
