<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service;
use App\Order;
use Carbon\Carbon;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\CronDetail;

class PickJobWinner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickjobwinner:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run daily one time, remind buyer to pick job winner after 7 days,15 days, 30 days from create job';

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
     * @return int
     */
    public function handle()
    {

        $this->info('begin : cron');
        $cron_details_obj = new CronDetail;
        if($cron_details_obj->start('pickjobwinner:remider')) {
            $after5Days = Date("Y-m-d",strtotime(Carbon::now()->subDays(5)));
            $after15Days = Date("Y-m-d",strtotime(Carbon::now()->subDays(15)));
            $after25Days = Date("Y-m-d",strtotime(Carbon::now()->subDays(25)));

            $jobs = Service::statusof('job')->doesntHave('job_accepted')
            ->select('id','uid','expire_on','created_at','title','seo_url')
            ->whereRaw("(DATE(created_at) = ? OR DATE(created_at) = ? OR DATE(created_at) = ?)", [$after5Days,$after15Days,$after25Days])
            ->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'))
            ->whereHas('job_offers', function($q){
                $q->where('is_hide', 0)->select('id');
            })
            ->whereHas('user', function($q){
                $q->where('is_unsubscribe', 0)->select('id');
            })
            ->get();

            if(sizeof($jobs) == 0) {
                $this->info('----No emails to send----');
            }
            foreach ($jobs as $job) {
                $expire_date_to_show = Carbon::parse($job->expire_on)->format('Y-m-d');
                $created_at = Carbon::parse($job->created_at)->format('Y-m-d');
                $content = '';
                if($created_at == $after5Days) {
                    $subject = "Pick a job winner! Bids are starting to come in for your job posting!";
                    $body_subject = "Bids are starting to come in for your job posting!";
                    $content = 'Congratulations! Bids are starting to come in for your job posting! Log in to your account now and start checking out the bids to choose the perfect freelancer to help you get more stuff done.';
                } else if($created_at == $after15Days) {
                    $subject = "Pick a job winner! Your job will expire shortly!";
                    $body_subject = "Your job will expire shortly!";
                    $content = 'You’ve got some great bids on your job posting so why not take a few moments to check them out and select a freelancer who’s the perfect fit for your needs.';
                } else if($created_at == $after25Days) {
                    $subject = "Pick a job winner! Your job will expire shortly!";
                    $body_subject = "Your job will expire shortly!";
                    $content = 'Your job posting will expire tomorrow, so now’s the time to make sure you check your post and select a freelancer to help you get more stuff done!';
                }
                //if($created_at == $after5Days) {
                    $data = [
                        'receiver_secret' => $job->user->secret,
                        'email_type' => 3,
                        'subject' => $subject,
                        'body_subject' => $body_subject,
                        'template' => 'frontend.emails.v1.pick_job_winner',
                        'email_to' => $job->user->email,
                        'name' => $job->user->Name,
                        'expire_date' => $expire_date_to_show,
                        'job_title' => $job->title,
                        'job_url' => route('show.job_detail',[$job->seo_url]),
                        'content' => $content,
                    ];
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    $this->info('Mail send to : '.$job->user->email);
                    $this->info('Job : '.$job->title);
            // }
            }
            $cron_details_obj->end('pickjobwinner:remider');
        }
        $this->info('end : cron');
    }
}
