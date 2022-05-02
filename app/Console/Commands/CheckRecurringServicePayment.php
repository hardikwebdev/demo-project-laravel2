<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\OrderSubscription;
use App\Order;
use Illuminate\Support\Facades\Mail;
use App\CronDetail;
use Carbon\Carbon;
use App\OrderSubscriptionHistory;
use Srmklive\PayPal\Services\ExpressCheckout;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\SellerEarning;

class CheckRecurringServicePayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:recurringservicepayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run every 15 minutes, to check if payment get for recurring service profile, if not get in 2 days then it will cancel order, and buyer can not start order until payment received';

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
        if($cron_details_obj->start('check:recurringservicepayment')) {
            date_default_timezone_set('America/Los_Angeles');

            $orderSubscription = OrderSubscription::select('*')
                ->where('is_payment_received',0)
                ->where('profile_id','!=','')
                ->orderBy('id','desc')
                ->get();

            if(count($orderSubscription) > 0){
                foreach ($orderSubscription as $row) {
                    $is_payment_received = app('App\Http\Controllers\PaypalPaymentController')->checkRecurringPaymentReceive($row->profile_id);
                    sleep(2);
                    if($is_payment_received == true){

                        $this->provider = new ExpressCheckout();
                        $response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($row->profile_id);

                        /*Payment received*/
                        $row->last_buyer_payment_date = date("Y-m-d");
                        $row->receipt = json_encode($response_profile_detail);
                        $row->is_payment_received = 1;
                        $row->save();

                        //Create first child order for courses
                        if($row->order->is_course == 1){
                            $receipt = (array)json_decode($row->receipt);
                            app('App\Http\Controllers\PaypalPaymentController')->createNewChildRecurringOrder($row->order,$receipt);
                        }

                        //Update payment date on seller earning
                        Order::storeSellerEarningPaymentDate($row->order);
                        

                        //create subscription history
                        app('App\Http\Controllers\PaypalPaymentController')->storeRecurringHistory($response_profile_detail,$row->order_id,$row->profile_id,'initial',$row->order->order_total_amount);

                        /*begin : Send conformation mail to buyer to start order*/
                        $data = [
                            'receiver_secret' => $row->order->user->secret,
		                    'email_type' => 1,
                            'subject' => 'demo - Your order subscription is setup',
                            'template' => 'frontend.emails.v1.confirm_subscription_reminder',
                            'email_to' => $row->order->user->email,
                            'username' => $row->order->user->username,
                            'seller' => $row->order->seller->username,
                            'orderDetail' => $row->order
                        ];
                        QueueEmails::dispatch($data, new SendEmailInQueue($data));

                        /*end : Send conformation mail to seller to start order*/

                        $this->info('Profile : #'.$row->profile_id.'- Received payment');

                    }else{
                        $date = new \DateTime($row->created_at);
                        $now = new \DateTime();
                        $days_diff =  $date->diff($now)->format("%d");
                        if($days_diff >= 2){

                            /* change payment status as not received*/
                            $row->is_payment_received = 2;
                            $row->is_cancel = 1;
                            $row->save();

                            /*Cancel subscription if active*/
                            app('App\Http\Controllers\PaypalPaymentController')->cancelPremiumOrder($row->profile_id);
                            
                            /*make order cancel without seller payment if active or delivered*/
                            $order = Order::where('status','!=','cancelled')->where('id',$row->order_id)->first();
                            if(count($order) > 0){
                                $order->status = 'cancelled';
                                $order->cancel_date = date('Y-m-d H:i:s');
                                $order->cancel_note = 'Cancel order due to recurring payment fails';
                                $order->save();

                                //Update last child recurrence (cancel order process)
					            app('App\Http\Controllers\PaypalPaymentController')->updateLastChildRecurrence($order);


                                $this->info('Profile : #'.$row->profile_id.'- Not Received payment in 2 days');
                            }else{
                                $this->info('Profile : #'.$row->profile_id.'- Already cancelled order');
                            }
                        }else{
                            $this->info('Profile : #'.$row->profile_id.'- continue to check in 2 days');
                        }
                    }
                }
            }else{
                $this->info('No any order subscription found');
            }
            $cron_details_obj->end('check:recurringservicepayment');
            exit();
        }
    }
}
