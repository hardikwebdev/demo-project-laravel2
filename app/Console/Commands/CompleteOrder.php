<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Order;
use App\EmailTemplate;
use App\User;
use App\Service;
use App\BuyerTransaction;
use App\SellerEarning;
use App\Notification;
use App\Message;
use Auth;
use App\CronDetail;

class CompleteOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Orders complete by admin. complete delivered order if no dispute is found, if found dispute then check "dispute on seller side". if yes, then make order complete & if no, then skip that order.';

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
        $this->info("Cron started...");
        $cron_details_obj = new CronDetail;

        if($cron_details_obj->start('order:complete')) {
            $OrderObj = new Order;

            $date = date("Y-m-d h:i:s",strtotime("- 3 day", strtotime("now")));

            $orders = Order::where("status","delivered")
            ->where("delivered_date",'<',$date)
            ->where('is_recurring',0)
            ->where('is_course',0)
            ->whereRaw("(is_dispute = 0 or (is_dispute = 1 and dispute_favour = 2))")
            ->limit(50)
            ->get();  

            //dd($orders->toArray()) ;
            
            if(!$orders->isEmpty()){
                foreach ($orders as $key => $value) {
                
                    if($value->no_of_revisions > 0){
                    if(!is_null($value->last_order_revision)){
                            if(strtotime($value->last_order_revision->updated_at) >= strtotime($date)){
                                continue;
                            }
                        }
                    }

                    /*Make order complete*/
                    $order_update = $OrderObj->complete_order($value->id,'');
                    if($order_update == true)
                    {
                        $this->info($value->order_no." Order completed");
                    }

                }
            }
            else
            {
                $this->info("No order found");
            }
            $cron_details_obj->end('order:complete');
        }

        $this->info("Cron end...");

    }
}