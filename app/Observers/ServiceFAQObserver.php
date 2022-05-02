<?php

namespace App\Observers;

use App\ServiceFAQ;
use App\Service;
use App\TrackServiceChange;

class ServiceFAQObserver
{
    /**
     * Handle the service f a q "created" event.
     *
     * @param  \App\ServiceFAQ  $serviceFAQ
     * @return void
     */
    public function created(ServiceFAQ $serviceFAQ)
    {
        $course = Service::withoutGlobalScope('is_course')->select('id')->where('is_course',1)->find($serviceFAQ->service_id);
        $column_name = "question";
        $extra_note = "Add FAQ";
        if(!empty($course)){
            $column_name = "instructions";
            $extra_note = "Add Requirement";
        }

        $tracker = new TrackServiceChange;
        $tracker->service_id = $serviceFAQ->service_id;
        $tracker->column_key = $column_name;
        $tracker->extra_note = $extra_note;
        $tracker->new_value = $serviceFAQ->question;
        $tracker->save();
    }

    /**
     * Handle the service f a q "updated" event.
     *
     * @param  \App\ServiceFAQ  $serviceFAQ
     * @return void
     */
    public function updated(ServiceFAQ $serviceFAQ)
    {
        if($serviceFAQ->wasChanged()) {
            $course = Service::withoutGlobalScope('is_course')->select('id')->where('is_course',1)->find($serviceFAQ->service_id);
            $column_name = "question";
            if(!empty($course)){
                $column_name = "instructions";
            }
            if($serviceFAQ->isDirty('question')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $serviceFAQ->service_id;
                $tracker->column_key = $column_name;
                $tracker->old_value = $serviceFAQ->getOriginal('question');
                $tracker->new_value = $serviceFAQ->question;
                $tracker->save();
            } 
            if($serviceFAQ->isDirty('answer')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $serviceFAQ->service_id;
                $tracker->column_key = 'answer';
                $tracker->old_value = $serviceFAQ->getOriginal('answer');
                $tracker->new_value = $serviceFAQ->answer;
                $tracker->save();
            } 
        }
    }

    /**
     * Handle the service f a q "deleted" event.
     *
     * @param  \App\ServiceFAQ  $serviceFAQ
     * @return void
     */
    public function deleted(ServiceFAQ $serviceFAQ)
    {
        $tracker = new TrackServiceChange;
        $tracker->service_id = $serviceFAQ->service_id;
        $tracker->column_key = 'question';
        $tracker->new_value = $serviceFAQ->question;
        $tracker->extra_note = "Deleted FAQ";
        $tracker->save();
    }

    /**
     * Handle the service f a q "restored" event.
     *
     * @param  \App\ServiceFAQ  $serviceFAQ
     * @return void
     */
    public function restored(ServiceFAQ $serviceFAQ)
    {
        //
    }

    /**
     * Handle the service f a q "force deleted" event.
     *
     * @param  \App\ServiceFAQ  $serviceFAQ
     * @return void
     */
    public function forceDeleted(ServiceFAQ $serviceFAQ)
    {
        //
    }
}
