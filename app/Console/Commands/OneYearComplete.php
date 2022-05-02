<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\EmailTemplate;
use App\CronDetail;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;

class OneYearComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:oneyear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated Email Celebrating demo Anniversary, After 1 Year of Becoming User.';

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
        if($cron_details_obj->start('user:oneyear')) {
            $users = User::where("last_login_at","!=",0)
            ->whereRaw('DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 Year)')
            ->where("last_login_at","!=",null)
            ->where("status","1")
            ->where("is_active","1")
            ->where('is_unsubscribe',0)
            ->get();

            if(count($users)){
                foreach ($users as $key => $value) {

                    $data = [
                        'receiver_secret' => $value->secret,
                        'email_type' => 7,
                        'subject' => 'You’re awesome',
                        'template' => 'frontend.emails.v1.one_year_completion',
                        'email_to' => $value->email,
                        'firstname' => $value->Name,
                    ];
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));

                    /*Send mail to sub users*/
                    $userObj = new User;
                    $userObj->send_mail_to_subusers('is_promotion_mail',$value->id,$data,'firstname');

                    // $this->info('user:  '.$value->email.' created at: '.$value->created_at);
                }
                $this->info('Total records found:  '.count($users));
            }else{
                $this->info('No user found.');
            }
            $cron_details_obj->end('user:oneyear');
        }
    }
}
