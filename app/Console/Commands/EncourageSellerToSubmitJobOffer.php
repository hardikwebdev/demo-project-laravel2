<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\CronDetail;
use App\Service;
use App\Order;
use App\User;
use Carbon\Carbon;

class EncourageSellerToSubmitJobOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encourageseller:submitjoboffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'When a new job is posted that may match a seller’s skillset. In order to find this, lets use a keyword related search based on the seller’s listed services. Send maximum of one email every 4 days to the seller, send it at 11am EST.';

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
        $this->info('==== Process - start =====');
        $cron_details_obj = new CronDetail;
        if($cron_details_obj->start('encourageseller:submitjoboffer')) {
            $keywordlist = Service::statusof('job')->whereNotNull('tags')->orderBy('created_at','desc')->limit(200)->pluck('tags');
            $keywords = [];
            foreach ($keywordlist as $value) {
                if(strpos($value, ',') !== false){
                    $temp = explode(',',$value);
                    $keywords = array_merge($keywords,$temp);
                } else {
                    array_push($keywords,$value);
                }
            }
            $keywords = array_unique($keywords);
            $keywords = array_values($keywords);
        
            $sellers = Service::statusof('service')->with(['user' => function ($query) {
                                    $query->select('id','Name','email');
                                }])
            					->whereHas('user', function ($query1) {
					                $query1->where('is_unsubscribe', 0)->select('id');
					            })
                                ->where(function ($query) use($keywords) {
                                    for ($i = 0; $i < count($keywords); $i++){
                                        $query->orWhere('title', 'like',  '%' . $keywords[$i] .'%');
                                    }      
                                })
                                ->groupBy('uid')
                                ->select('id','uid','title')
                                ->inRandomOrder()->limit(10)->get();
            
            foreach ($sellers as $value) {
                $uid = $value->uid;
                $services = Service::statusof('service')->where('uid',$uid)->pluck('title');
                $user_tags = [];
                foreach ($services as $service) {
                    foreach ($keywords as $val) {
                        if(strpos($service, $val) !== false){
                            if(!in_array($val, $user_tags)) {
                                array_push($user_tags, $val);
                            }
                        }
                    }
                }
                $jobs = Service::statusof('job')->whereNotNull('tags')
                                ->where('uid','!=',$uid)
                                ->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'))
                                ->doesntHave('job_accepted')
                                ->whereDoesntHave('job_offers', function($query) use($uid){
                                    $query->where('seller_id',$uid);
                                })
                                ->where(function ($query) use($user_tags) {
                                    for ($i = 0; $i < count($user_tags); $i++){
                                        $query->orWhere('tags', 'like',  '%' . $user_tags[$i] .'%');
                                    }      
                                })
                                ->select('id','title','seo_url','descriptions','tags','job_min_price','job_max_price','expire_on','created_at')
                                ->inRandomOrder()->limit(5)->get();
                $value['jobs'] = $jobs;
            }

            foreach ($sellers as $key => $value) {
                if(count($value->jobs) > 0) {
                    $data = [
                        'receiver_secret' => $value->user->secret,
                        'email_type' => 3,
                        'subject' => 'Looking to make some more sales?',
                        'template' => 'frontend.emails.v1.encourage_seller_to_submit_job_offer',
                        'email_to' => $value->user->email,
                        'firstname' => $value->user->Name,
                        'jobs' => $value->jobs,
                    ];
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    $this->info('Email sent successfully to:  '.$value->user->email);

                    /*Send mail to sub users*/
                    $userObj = new User;
                    $userObj->send_mail_to_subusers('is_promotion_mail',$value->user->id,$data,'firstname');
                    
                }
            }
            
            $cron_details_obj->end('encourageseller:submitjoboffer');
        }
        $this->info('==== Process - end =====');
    }
}
