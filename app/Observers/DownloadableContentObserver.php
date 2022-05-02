<?php

namespace App\Observers;

use App\DownloadableContent;
use App\TrackServiceChange;

class DownloadableContentObserver
{
    /**
     * Handle the downloadable content "created" event.
     *
     * @param  \App\DownloadableContent  $downloadableContent
     * @return void
     */
    public function created(DownloadableContent $downloadableContent)
    {
        $tracker = new TrackServiceChange;
        $tracker->service_id = $downloadableContent->course_id;
        $tracker->column_key = 'downloadable_content';
        $tracker->new_value =  '<a href="'.$downloadableContent->url.'" target="_blank">'.$downloadableContent->url.'</a>';
        $tracker->extra_note = "Added Downloadable Content";
        $tracker->save();
    }

    /**
     * Handle the downloadable content "updated" event.
     *
     * @param  \App\DownloadableContent  $downloadableContent
     * @return void
     */
    public function updated(DownloadableContent $downloadableContent)
    {
        //
    }

    /**
     * Handle the downloadable content "deleted" event.
     *
     * @param  \App\DownloadableContent  $downloadableContent
     * @return void
     */
    public function deleted(DownloadableContent $downloadableContent)
    {
        //
    }

    /**
     * Handle the downloadable content "restored" event.
     *
     * @param  \App\DownloadableContent  $downloadableContent
     * @return void
     */
    public function restored(DownloadableContent $downloadableContent)
    {
        //
    }

    /**
     * Handle the downloadable content "force deleted" event.
     *
     * @param  \App\DownloadableContent  $downloadableContent
     * @return void
     */
    public function forceDeleted(DownloadableContent $downloadableContent)
    {
        //
    }
}
