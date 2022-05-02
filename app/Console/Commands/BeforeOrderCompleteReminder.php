<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Order;
use App\User;
use Auth;
use App\CronDetail;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;

class BeforeOrderCompleteReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every 5 min: Order complete reminder before 24 hour';

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
        if($cron_details_obj->start('order:reminder')) {
            $from_date = date("Y-m-d H:i:s",strtotime("+ 1 day -6 minutes"));
            $to_date = date("Y-m-d H:i:s",strtotime("+ 1 day"));
            
            $Orders = Order::with('service','user','seller')->select('id','order_no','uid','seller_uid','service_id')->where("status","active")
                ->whereBetween('end_date', array($from_date, $to_date))
                ->whereNull('complete_notify_on')
                ->where('is_recurring',0)
                ->where('is_course',0)
                ->get();
            
            if(count($Orders)){
                /* Send Email to Seller */
                foreach ($Orders as $Order) {

                    $model = Order::find($Order->id);
                    $model->complete_notify_on = date('Y-m-d H:i:s');
                    $model->save();

                    $data = [
                        'receiver_secret' => $Order->seller->secret,
		                'email_type' => 1,
                        'subject' => 'Warning: You have 24 hours to finish working on your order',
                        'template' => 'frontend.emails.v1.order_complete_reminder',
                        'email_to' => $Order->seller->email,
                        'username' => $Order->seller->username,
                        'orderNumber' => $Order->order_no,
                        'servicename' => $Order->service->title
                    ];
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));

                    /*Send mail to sub users*/
                    $userObj = new User;
                    $userObj->send_mail_to_subusers('is_order_mail',$Order->seller->id,$data,'username');

                    $this->info("Mail send to: " . $Order->seller->email);
                }

                $this->info("Total mail sent to : " . count($Orders));
            }else{
                $this->info("No order found for due date less than 24hour.");
            }
            $cron_details_obj->end('order:reminder');
        }
    }
}
