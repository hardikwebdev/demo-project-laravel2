<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SubscribeUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\CronDetail;

class SubscriptionReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'premiumsubscribepayment:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily, Reminder Mail Send Before 3 days on expiry date of recurring';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cron_details_obj = new CronDetail;
        if($cron_details_obj->start('premiumsubscribepayment:reminder')) {
            $startDateDate = Carbon::now();
            $beforeThreeDays = Date("Y-m-d",strtotime($startDateDate->addDays(3)));
        
            $allUser = SubscribeUser::whereDate('end_date','<=', $beforeThreeDays)
            ->whereDate('end_date','>=', $beforeThreeDays)
            ->whereNotIn('user_id',['17608','942','3111','38','14','17574'])
            ->where('is_cancel',0)->get();

            if(!$allUser->isempty()){
                foreach ($allUser as $users) {
                    $usersdata = $users->seller;
                    $data = ["email" => $usersdata->email,'name' => $usersdata->Name,'date' => $users->end_date];

                    $mail = Mail::send(['html' => 'frontend.emails.reminder'], $data, function($message) use($data) {
                        $message->to($data['email'])->subject('demo - Premium subscription renewal reminder');
                    });
                }
            }
            $cron_details_obj->end('premiumsubscribepayment:reminder');
        }
    }
}
