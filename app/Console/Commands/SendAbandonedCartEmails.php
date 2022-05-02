<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Setting;
use App\Cart;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\CronDetail;
use App\Order;

class SendAbandonedCartEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abandonedcartemail:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If a cart have entry , then sending mails at fix intervals';

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
    	if($cron_details_obj->start('abandonedcartemail:send')) {
    		$current_date = date('Y-m-d H:i:s');
    		$settings = Setting::find(1)->first();
    		$abandoned_cart = json_decode($settings->abandoned_cart_email);
    		$carts = Cart::with('user_email')->whereNotNull('email_index')->whereNotNull('email_send_at')
    		->where('email_send_at','<=',$current_date)
    		->whereHas('user_email', function($q){
    			$q->where('is_unsubscribe', 0)->select('id');
    		})
    		->groupBy('uid')
    		->get();
    		foreach($carts as $index => $cart) {
    			$i = $cart->email_index;
    			if($cart->email_index == 1) {
                    //send email
    				$data = [
    					'subject' => 'Hey, '.$cart->user_email->Name.'!  Come back!  You forgot your stuff!',
    					'template' => 'frontend.emails.v1.abandoned_cart_first_email',
    					'email_to' => $cart->user_email->email,
    					'user_name' => $cart->user_email->Name,
    					'id' => $cart->uid
    				];

    				$this->info('==== send email to ====='.$cart->user_email->email);
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));
    			} else if($cart->email_index == 2) {
                    //send email
    				$data = [
    					'subject' => 'Having Non-Buyer’s Remorse?',
    					'template' => 'frontend.emails.v1.abandoned_cart_second_email',
    					'email_to' => $cart->user_email->email,
    					'user_name' => $cart->user_email->Name,
    					'category' => $cart->service->category->category_name
    				];

    				$this->info('==== send email to ====='.$cart->user_email->email);
    				QueueEmails::dispatch($data, new SendEmailInQueue($data));
    			} else if($cart->email_index == 3) {
                    //send email
                    /* $data = [
                        'subject' => 'Just for you:  [XX]% off!',
                        'template' => 'frontend.emails.v1.abandoned_cart_third_email',
                        //'email_to' => $cart->user_email->email,
                        'user_name' => $cart->user_email->Name,
                    ];

                    QueueEmails::dispatch($data, new SendEmailInQueue($data)); */
                } else if($cart->email_index == 4) {
                	$service_id = Cart::where('uid',$cart->uid)->pluck('service_id');

                    $review = Order::whereIn('service_id',$service_id)
                        ->where('seller_rating' ,'>',0)
                        ->select('seller_rating')
                        ->orderBy('seller_rating', 'desc')
                        ->orderBy('review_date','desc')
                        ->first();

                    if(!empty($review) && $review->seller_rating == 5){
                        //send email
                        $data = [
                            'subject' => 'Have you heard? demo is awesome!',
                            'template' => 'frontend.emails.v1.abandoned_cart_fourth_email',
                            'email_to' => $cart->user_email->email,
                            'user_name' => $cart->user_email->Name,
                            'service_id' => $service_id
                        ];

                        $this->info('==== send email to ====='.$cart->user_email->email);
                        QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    }
                }

                // update database
                $email_index = ($i < sizeof($abandoned_cart)) ? $i + 1 : null;
                $email_send_at = ($i < sizeof($abandoned_cart)) ? date('Y-m-d H:i:s', strtotime($cart->created_at. ' + '.$abandoned_cart[$i]->duration.' '.$abandoned_cart[$i]->span.'s')) : null;
                Cart::where('uid', $cart->uid)->where('is_job',0)->where('is_custom_order',0)->update(['email_index' => $email_index, 'email_send_at' => $email_send_at]);
            }
            $cron_details_obj->end('abandonedcartemail:send');
        }
        $this->info('==== Process - end =====');
    }
}