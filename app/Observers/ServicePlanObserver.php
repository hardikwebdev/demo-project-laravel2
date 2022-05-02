<?php

namespace App\Observers;

use App\ServicePlan;
use App\TrackServiceChange;

class ServicePlanObserver
{
    /**
     * Handle the service plan "created" event.
     *
     * @param  \App\ServicePlan  $servicePlan
     * @return void
     */
    public function created(ServicePlan $servicePlan)
    {
        if($servicePlan->plan_type == 'standard' || $servicePlan->plan_type == 'premium') {
            $tracker = new TrackServiceChange;
            $tracker->service_id = $servicePlan->service_id;
            $tracker->column_key = 'price';
            $tracker->new_value = $servicePlan->price;
            $tracker->extra_note = 'Added new plan for plan type : ' .$servicePlan->plan_type .', and delivery_days : '.$servicePlan->delivery_days.', and no_of_revisions : '.$servicePlan->no_of_revisions;
            $tracker->save();
        }
    }

    /**
     * Handle the service plan "updated" event.
     *
     * @param  \App\ServicePlan  $servicePlan
     * @return void
     */
    public function updated(ServicePlan $servicePlan)
    {
        if($servicePlan->wasChanged()) {
            if($servicePlan->isDirty('package_name')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $servicePlan->service_id;
                $tracker->column_key = 'package_name';
                $tracker->old_value = $servicePlan->getOriginal('package_name');
                $tracker->new_value = $servicePlan->package_name;
                $tracker->extra_note = 'Updated for plan type : ' .$servicePlan->plan_type;
                $tracker->save();
            }
            if($servicePlan->isDirty('offering_details')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $servicePlan->service_id;
                $tracker->column_key = 'offering_details';
                $tracker->old_value = $servicePlan->getOriginal('offering_details');
                $tracker->new_value = $servicePlan->offering_details;
                $tracker->extra_note = 'Updated for plan type : ' .$servicePlan->plan_type;
                $tracker->save();
            }
            if($servicePlan->isDirty('delivery_days')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $servicePlan->service_id;
                $tracker->column_key = 'delivery_days';
                $tracker->old_value = $servicePlan->getOriginal('delivery_days');
                $tracker->new_value = $servicePlan->delivery_days;
                $tracker->extra_note = 'Updated for plan type : ' .$servicePlan->plan_type;
                $tracker->save();
            }
            if($servicePlan->isDirty('price')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $servicePlan->service_id;
                $tracker->column_key = 'price';
                $tracker->old_value = $servicePlan->getOriginal('price');
                $tracker->new_value = $servicePlan->price;
                $tracker->extra_note = 'Updated for plan type : ' .$servicePlan->plan_type;
                $tracker->save();
            }
            if($servicePlan->isDirty('review_edition_price')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $servicePlan->service_id;
                $tracker->column_key = 'review_edition_price';
                $tracker->old_value = $servicePlan->getOriginal('review_edition_price');
                $tracker->new_value = $servicePlan->review_edition_price;
                $tracker->extra_note = 'Updated for plan type : ' .$servicePlan->plan_type;
                $tracker->save();
            }
            if($servicePlan->isDirty('no_of_revisions')){
                $tracker = new TrackServiceChange;
                $tracker->service_id = $servicePlan->service_id;
                $tracker->column_key = 'no_of_revisions';
                $tracker->old_value = $servicePlan->getOriginal('no_of_revisions');
                $tracker->new_value = $servicePlan->no_of_revisions;
                $tracker->extra_note = 'Updated for plan type : ' .$servicePlan->plan_type;
                $tracker->save();
            }
        }
    }

    /**
     * Handle the service plan "deleted" event.
     *
     * @param  \App\ServicePlan  $servicePlan
     * @return void
     */
    public function deleted(ServicePlan $servicePlan)
    {
        //
    }

    /**
     * Handle the service plan "restored" event.
     *
     * @param  \App\ServicePlan  $servicePlan
     * @return void
     */
    public function restored(ServicePlan $servicePlan)
    {
        //
    }

    /**
     * Handle the service plan "force deleted" event.
     *
     * @param  \App\ServicePlan  $servicePlan
     * @return void
     */
    public function forceDeleted(ServicePlan $servicePlan)
    {
        //
    }
}
