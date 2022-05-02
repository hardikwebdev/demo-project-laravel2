<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\User;
use App\CronDetail;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;

class ReengagementSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reengagement:sequence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule daily one time, send mail to that anyone who hasn’t opened an email in the last six months';

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
    	if($cron_details_obj->start('reengagement:sequence')) {

    		$four_weeks = Date("Y-m-d",strtotime(Carbon::now()->subWeeks(4)));
    		$five_weeks = Date("Y-m-d",strtotime(Carbon::now()->subWeeks(5)));
    		$six_weeks = Date("Y-m-d",strtotime(Carbon::now()->subWeeks(6)));
    		$six_weeks_and_two_days = Date("Y-m-d",strtotime(Carbon::now()->subWeeks(6)->subDays(2)));

    		$this->info('four_weeks: '.Date("Y-m-d",strtotime(Carbon::now()->subWeeks(4))));
    		$this->info('five_weeks: '.Date("Y-m-d",strtotime(Carbon::now()->subWeeks(5))));
    		$this->info('six_weeks: '.Date("Y-m-d",strtotime(Carbon::now()->subWeeks(6))));
    		$this->info('six_weeks_and_two_days: '.Date("Y-m-d",strtotime(Carbon::now()->subWeeks(6)->subDays(2))));

    		$users = User::select('id','Name','email','username','is_unsubscribe','notification','last_login_at')
            ->where('status', 1)
            ->where('is_delete', 0)
    		->where('is_unsubscribe',0)
    		->whereNotNull('last_login_at')
    		->whereRaw("(DATE(last_login_at) = ? OR DATE(last_login_at) = ? OR DATE(last_login_at) = ? OR DATE(last_login_at) = ?)", [$four_weeks,$five_weeks,$six_weeks,$six_weeks_and_two_days])
    		->get();

    		foreach ($users as $user) {
    			$last_login_at = Carbon::parse($user->last_login_at)->format('Y-m-d');
    			$subject = $template = '';

    			/*Send mail for unsubscribe mail*/
    			if($last_login_at == $four_weeks) {
    				$subject = "It's been a while...";
    				$template = 'frontend.emails.v1.reengagement_sequence_first';
    			} else if($last_login_at == $five_weeks) {
    				$subject = "Here's the latest hot TIP from demo!";
    				$template = 'frontend.emails.v1.reengagement_sequence_second';
    			} else if($last_login_at == $six_weeks) {
    				$subject = "If it's time to go...";
    				$template = 'frontend.emails.v1.reengagement_sequence_third';
    			} else if($last_login_at == $six_weeks_and_two_days) {
    				$subject = "We hate to see you go!";
    				$template = 'frontend.emails.v1.reengagement_sequence_forth';

    				/*Un-subscribe from mail lists*/
    				$user->is_unsubscribe = 1;
		            $user->notification = 0;
		            $user->save();

    			}

    			if($subject != '' && $template != '') {
    				$data = [
    					'subject' => $subject,
    					'template' => $template,
    					'email_to' => $user->email,
    					'name' => $user->Name,
    					'username' => $user->username
    				];
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));

    				$this->info('subject : '.$subject);
    				$this->info('Mail send to : '.$user->email);
    			}
    		}
    		$cron_details_obj->end('reengagement:sequence');
    	}
    	$this->info('==== Process - end =====');
    }
}
