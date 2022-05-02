<?php

namespace App\Observers;

use App\ServiceQuestion;
use App\TrackServiceChange;

class ServiceQuestionObserver
{
    /**
     * Handle the service question "created" event.
     *
     * @param  \App\ServiceQuestion  $serviceQuestion
     * @return void
     */
    public function created(ServiceQuestion $serviceQuestion)
    {
        $session_data = \Session::get('_ServiceQuestion_Old');
        $count = 0;
        foreach ($session_data as $key => $value) {
            if($value->question == $serviceQuestion->question && $value->answer_type == $serviceQuestion->answer_type) {
                $count++;
            }
        }
        if($count == 0) {
            $tracker = new TrackServiceChange;
            $tracker->service_id = $serviceQuestion->service_id;
            $tracker->column_key = 'Service Question - question';
            $tracker->new_value = $serviceQuestion->question;
            $tracker->extra_note = "Added Service Question - answer_type : ".$serviceQuestion->answer_type;
            $tracker->save();
        }
    }

    /**
     * Handle the service question "updated" event.
     *
     * @param  \App\ServiceQuestion  $serviceQuestion
     * @return void
     */
    public function updated(ServiceQuestion $serviceQuestion)
    {
        //
    }

    /**
     * Handle the service question "deleted" event.
     *
     * @param  \App\ServiceQuestion  $serviceQuestion
     * @return void
     */
    public function deleted(ServiceQuestion $serviceQuestion)
    {
        $session_data = \Session::get('_ServiceQuestion_New');
        $count = 0;
        foreach ($session_data as $key => $value) {
            if($value['question_info'] == $serviceQuestion->question && $value['answer_info'] == $serviceQuestion->answer_type) {
                $count++;
            }
        }
        if($count == 0) {
            $tracker = new TrackServiceChange;
            $tracker->service_id = $serviceQuestion->service_id;
            $tracker->column_key = 'Service Question - question';
            $tracker->new_value = $serviceQuestion->question;
            $tracker->extra_note = "Deleted Service Question - answer_type : ".$serviceQuestion->answer_type;
            $tracker->save();
        }
    }

    /**
     * Handle the service question "restored" event.
     *
     * @param  \App\ServiceQuestion  $serviceQuestion
     * @return void
     */
    public function restored(ServiceQuestion $serviceQuestion)
    {
        //
    }

    /**
     * Handle the service question "force deleted" event.
     *
     * @param  \App\ServiceQuestion  $serviceQuestion
     * @return void
     */
    public function forceDeleted(ServiceQuestion $serviceQuestion)
    {
        //
    }
}
