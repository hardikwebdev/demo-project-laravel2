<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CompleteOrder::class,
        Commands\InactiveAccount::class,
        Commands\UpdateSellerLevel::class,
        Commands\BeforeOrderCompleteReminder::class,
        Commands\OneYearComplete::class,
        Commands\S3Upload::class,
        Commands\DisputeFavour::class,
        Commands\Refund::class,
        Commands\StartOrder::class,
        Commands\CustomChange::class,
        Commands\updatePaypalExpiryDate::class,
        Commands\RecurringServicePayment::class,
        Commands\Test::class,
        Commands\SubscriptionReminder::class,
        Commands\RecurringPaymentReminder::class,
        Commands\CheckRecurringServicePayment::class,
        Commands\CheckPremiumPayment::class,
        Commands\UpdateOnTimeDelivery::class,
        /*,
        Commands\InactiveAccount::class*/
        Commands\UpdateEscotAmount::class,
        Commands\OldCategoryChanges::class,
        Commands\SendAbandonedCartEmails::class,
        Commands\RemoveFileUptoLimitForPremiumUser::class,
        Commands\AddCancelOrderLateReviewToSeller::class,
        Commands\addNoOfRevisionsInOrder::class,
        Commands\SendNotifications::class,
        Commands\RevisionOrderId::class,
        Commands\AddOldCategorySlot::class,
        Commands\StoreOrderTotalAmount::class,
        Commands\StoreUserIDInCouponsTable::class,
        Commands\DeleteServiceFromTrashAfter90Days::class,
        Commands\GeneralScript::class,
        Commands\WelcomeEmailsForNewUser::class,
        Commands\StoreSubscribeUserTransactionHistory::class,
        Commands\StoreRenewCountInSubscribeUsers::class,
        Commands\RenewPremiumSellerWalletSubscription::class,
        Commands\SendReminderEmailsForPremiumSellerSubscription::class,
        Commands\PickJobWinner::class,
        Commands\ReengagementSequence::class,
        Commands\EncourageSellerToSubmitJobOffer::class,
        Commands\SitemapDownload::class,
        Commands\RemoveTempOrder::class,
        Commands\RemoveUnwantedFiles::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
