<?php

namespace App\Observers;

use App\ContentMedia;
use App\TrackServiceChange;

class ContentMediaObserver
{
    /**
     * Handle the content media "created" event.
     *
     * @param  \App\ContentMedia  $contentMedia
     * @return void
     */
    public function created(ContentMedia $contentMedia)
    {
        $tracker = new TrackServiceChange;
        $tracker->service_id = $contentMedia->course_id;
        $tracker->column_key = 'content_name';
        $tracker->new_value =  ($contentMedia->media_url != "")? '<b>Content Media Link </b>: <a href="'.$contentMedia->media_url.'" target="_blank">'.$contentMedia->media_url.'</a>' : '<b>Content Article</b> :'.$contentMedia->article_text;
        $tracker->extra_note = "Added Content | Content Name: ".$contentMedia->name;
        $tracker->save();
    }

    /**
     * Handle the content media "updated" event.
     *
     * @param  \App\ContentMedia  $contentMedia
     * @return void
     */
    public function updated(ContentMedia $contentMedia)
    {
        if($contentMedia->wasChanged()) {
            /* Update Name Tracking */
            if($contentMedia->isDirty('name')){
                $tracker = new TrackServiceChange;
                $tracker->service_id    = $contentMedia->course_id;
                $tracker->column_key    = 'name';
                $tracker->old_value     = $contentMedia->getOriginal('name');
                $tracker->new_value     = $contentMedia->name;
                $tracker->extra_note    = '';
                $tracker->save();
            }
            /* Update Media Tracking */
            if($contentMedia->isDirty('media_url')){
                $tracker = new TrackServiceChange;
                $tracker->service_id    = $contentMedia->course_id;
                $tracker->column_key    = 'media';
                $tracker->old_value     = '<a href="'.$contentMedia->getOriginal('media_url').'" target="_blank">'.$contentMedia->getOriginal('media_url').'</a>';
                $tracker->new_value     = '<a href="'.$contentMedia->media_url.'" target="_blank">'.$contentMedia->media_url.'</a>';
                $tracker->extra_note    = '';
                $tracker->save();
            }
            /* Update Article Tracking */
            if($contentMedia->isDirty('article_text')){
                $tracker = new TrackServiceChange;
                $tracker->service_id    = $contentMedia->course_id;
                $tracker->column_key    = 'article_text';
                $tracker->old_value     = $contentMedia->getOriginal('article_text');
                $tracker->new_value     = $contentMedia->article_text;
                $tracker->extra_note    = '';
                $tracker->save();
            }
        }
    }

    /**
     * Handle the content media "deleted" event.
     *
     * @param  \App\ContentMedia  $contentMedia
     * @return void
     */
    public function deleted(ContentMedia $contentMedia)
    {
        //
    }

    /**
     * Handle the content media "restored" event.
     *
     * @param  \App\ContentMedia  $contentMedia
     * @return void
     */
    public function restored(ContentMedia $contentMedia)
    {
        //
    }

    /**
     * Handle the content media "force deleted" event.
     *
     * @param  \App\ContentMedia  $contentMedia
     * @return void
     */
    public function forceDeleted(ContentMedia $contentMedia)
    {
        //
    }
}
