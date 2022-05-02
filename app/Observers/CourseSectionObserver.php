<?php

namespace App\Observers;

use App\CourseSection;
use App\TrackServiceChange;

class CourseSectionObserver
{
    /**
     * Handle the course section "created" event.
     *
     * @param  \App\CourseSection  $courseSection
     * @return void
     */
    public function created(CourseSection $courseSection)
    {
        $tracker = new TrackServiceChange;
        $tracker->service_id = $courseSection->course_id;
        $tracker->column_key = 'section_name';
        $tracker->new_value = $courseSection->name;
        $tracker->extra_note = "Added Section";
        $tracker->save();
    }

    /**
     * Handle the course section "updated" event.
     *
     * @param  \App\CourseSection  $courseSection
     * @return void
     */
    public function updated(CourseSection $courseSection)
    {
        if($courseSection->wasChanged()) {
            if($courseSection->isDirty('name')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $courseSection->course_id;
                $tracker->column_key = 'section_name';
                $tracker->old_value = $courseSection->getOriginal('name');
                $tracker->new_value = $courseSection->name;
                $tracker->extra_note = "Update Section";
                $tracker->save();
            }
        }
    }

    /**
     * Handle the course section "deleted" event.
     *
     * @param  \App\CourseSection  $courseSection
     * @return void
     */
    public function deleted(CourseSection $courseSection)
    {
        //
    }

    /**
     * Handle the course section "restored" event.
     *
     * @param  \App\CourseSection  $courseSection
     * @return void
     */
    public function restored(CourseSection $courseSection)
    {
        //
    }

    /**
     * Handle the course section "force deleted" event.
     *
     * @param  \App\CourseSection  $courseSection
     * @return void
     */
    public function forceDeleted(CourseSection $courseSection)
    {
        //
    }
}
