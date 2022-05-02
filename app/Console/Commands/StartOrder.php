<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order;
use App\User;
use App\CronDetail;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;

class StartOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:startorder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add reminder to let buyers know they have an order that has not been started yet, remind them after 3 days at first, then, every 14 days after that.';

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
        if($cron_details_obj->start('reminder:startorder')) {

            $orders = Order::select('id','uid','order_no')
            ->where("status","new")
            ->whereRaw('( (DATEDIFF(CURDATE(), start_date) = 3) or (DATEDIFF(start_date, CURDATE())%14) = 0 )')
            ->get();   

            if(count($orders)){
                foreach ($orders as $key => $value) {

                    $user = User::select('id','email','username')->where('id', $value->uid)->first();

                    $data = [
                        'receiver_secret' => $user->secret,
                   	 	'email_type' => 1,
                        'subject' => 'Your order has not been started yet',
                        'template' => 'frontend.emails.v1.start_order_reminder',
                        'email_to' => $user->email,
                        'username' => $user->username,
                        'orderNumber' => $value->order_no,
                        'order_detail_url' => route('buyer_orders_details',$value->order_no)
                    ];
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    
                }
                $this->info('Total Order Found: '.count($orders));
            }else{
                $this->info('No Order Found.');
            }
            $cron_details_obj->end('reminder:startorder');
        }
    }
}
