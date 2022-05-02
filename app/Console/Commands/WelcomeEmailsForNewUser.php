<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\WelcomeEmail;
use Carbon\Carbon;
use App\CronDetail;

class WelcomeEmailsForNewUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'welcomeemailsfornewuser:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send welcome emails for new users. This script is run daily.';

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
    	if($cron_details_obj->start('welcomeemailsfornewuser:send')) {
    		$welcome = WelcomeEmail::whereDate('email_at',Carbon::today())
    		->whereHas('user', function($q){
    			$q->where('is_unsubscribe', 0)->select('id');
    		})->get();

    		foreach ($welcome as $key => $value) {
    			if($value->email_index == 1) {
    				$data = [
    					'subject' => 'You make the demo community great!',
    					'template' => 'frontend.emails.v1.welcome_user_second_email',
    					'email_to' => $value->user->email,
    					'firstname' => $value->user->Name,
    				];
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    //$this->info('==== send email to =====  '.$value->user->email);

                    // update database
    				$value->email_at = Carbon::parse($value->email_at)->addDays(1)->format('Y-m-d H:i:s');
    				$value->email_index = 2;
    				$value->save();

    			} else if($value->email_index == 2) {
    				$data = [
    					'subject' => 'Got demo questions?  We’ve got demo answers!',
    					'template' => 'frontend.emails.v1.welcome_user_third_email',
    					'email_to' => $value->user->email,
    					'firstname' => $value->user->Name,
    				];
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    //$this->info('==== send email to =====  '.$value->user->email);

                    // update database
    				$value->email_at = Carbon::parse($value->email_at)->addDays(1)->format('Y-m-d H:i:s');
    				$value->email_index = 3;
    				$value->save();

    			} else if($value->email_index == 3) {
    				$data = [
    					'subject' => 'Find the freelance service that’s right for you!',
    					'template' => 'frontend.emails.v1.welcome_user_fourth_email',
    					'email_to' => $value->user->email,
    					'firstname' => $value->user->Name,
    				];
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    //$this->info('==== send email to =====  '.$value->user->email);

                    // update database
    				$value->email_at = Carbon::parse($value->email_at)->addDays(1)->format('Y-m-d H:i:s');
    				$value->email_index = 4;
    				$value->save();

    			} else if($value->email_index == 4) {
    				$data = [
    					'subject' => 'The importance of leaving feedback',
    					'template' => 'frontend.emails.v1.welcome_user_fifth_email',
    					'email_to' => $value->user->email,
    					'firstname' => $value->user->Name,
    				];
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    //$this->info('==== send email to =====  '.$value->user->email);

                    // update database
    				$value->email_at = null;
    				$value->email_index = null;
    				$value->save();
    			}
    		}

    		$cron_details_obj->end('welcomeemailsfornewuser:send');
    	}
    	$this->info('==== Process - end =====');
    }
}