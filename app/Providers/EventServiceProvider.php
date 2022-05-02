<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Service;
use App\Observers\ServiceObserver;
use App\ServicePlan;
use App\Observers\ServicePlanObserver;
use App\ServiceMedia;
use App\Observers\ServiceMediaObserver;
use App\ServiceExtra;
use App\Observers\ServiceExtraObserver;
use App\ServiceQuestion;
use App\Observers\ServiceQuestionObserver;
use App\ServiceFAQ;
use App\Observers\ServiceFAQObserver;
use App\Order;
use App\Observers\OrderObserver;
use App\User;
use App\Observers\UserObserver;
use App\CourseSection;
use App\Observers\CourseSectionObserver;
use App\ContentMedia;
use App\Observers\ContentMediaObserver;
use App\DownloadableContent;
use App\Observers\DownloadableContentObserver;
use App\Models\IntroductionVideoHistory;
use App\Observers\UserIntroVideoObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
        ],
        'Illuminate\Auth\Events\PasswordReset' => [
            'App\Listeners\AfterPasswordReset',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Service::observe(ServiceObserver::class);
        ServicePlan::observe(ServicePlanObserver::class);
        ServiceMedia::observe(ServiceMediaObserver::class);
        ServiceExtra::observe(ServiceExtraObserver::class);
        ServiceQuestion::observe(ServiceQuestionObserver::class);
        ServiceFAQ::observe(ServiceFAQObserver::class);
        Order::observe(OrderObserver::class);
        User::observe(UserObserver::class);
        CourseSection::observe(CourseSectionObserver::class);
        ContentMedia::observe(ContentMediaObserver::class);
        DownloadableContent::observe(DownloadableContentObserver::class);
        IntroductionVideoHistory::observe(UserIntroVideoObserver::class);
    }
}
