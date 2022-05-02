<?php

namespace App\Observers;

use App\Models\IntroductionVideoHistory;
use App\User;
use App\Models\PendingApprovalCount;

class UserIntroVideoObserver
{
    /**
     * Handle the introduction video history "created" event.
     *
     * @param  \App\Models\IntroductionVideoHistory  $introductionVideoHistory
     * @return void
     */
    public function created(IntroductionVideoHistory $introductionVideoHistory)
    {
        $users_intro_video_count = User::select('users.id')->join('introduction_video_histories','introduction_video_histories.user_id','users.id')->where('introduction_video_histories.is_approved',0)->count();
        /* Update counter */ 
        PendingApprovalCount::updateRecord(['users_intro_videos'=>$users_intro_video_count]);
    }

    /**
     * Handle the introduction video history "updated" event.
     *
     * @param  \App\Models\IntroductionVideoHistory  $introductionVideoHistory
     * @return void
     */
    public function updated(IntroductionVideoHistory $introductionVideoHistory)
    {
        if($introductionVideoHistory->wasChanged()) {
            if($introductionVideoHistory->isDirty('is_approved')){
                $users_intro_video_count = User::select('users.id')->join('introduction_video_histories','introduction_video_histories.user_id','users.id')->where('introduction_video_histories.is_approved',0)->count();
                /* Update counter */ 
                PendingApprovalCount::updateRecord(['users_intro_videos'=>$users_intro_video_count]);
            }
        }
    }

    /**
     * Handle the introduction video history "deleted" event.
     *
     * @param  \App\Models\IntroductionVideoHistory  $introductionVideoHistory
     * @return void
     */
    public function deleted(IntroductionVideoHistory $introductionVideoHistory)
    {
        $users_intro_video_count = User::select('users.id')->join('introduction_video_histories','introduction_video_histories.user_id','users.id')->where('introduction_video_histories.is_approved',0)->count();
        /* Update counter */ 
        PendingApprovalCount::updateRecord(['users_intro_videos'=>$users_intro_video_count]);
    }

    /**
     * Handle the introduction video history "restored" event.
     *
     * @param  \App\Models\IntroductionVideoHistory  $introductionVideoHistory
     * @return void
     */
    public function restored(IntroductionVideoHistory $introductionVideoHistory)
    {
        //
    }

    /**
     * Handle the introduction video history "force deleted" event.
     *
     * @param  \App\Models\IntroductionVideoHistory  $introductionVideoHistory
     * @return void
     */
    public function forceDeleted(IntroductionVideoHistory $introductionVideoHistory)
    {
        //
    }
}
