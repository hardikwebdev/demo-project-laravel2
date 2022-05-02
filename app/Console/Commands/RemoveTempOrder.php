<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\CronDetail;
use Carbon\Carbon;
use App\TempOrder;
use App\TempOrderExtra;
use App\TempCoupanApplied;
use App\TempAffiliateEarning;
use App\Service;
use DB;

class RemoveTempOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:temporders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete 60 minute old temp orders created by cc payment for orders, schedule every 5 minutes';

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
        $cron_details_obj = new CronDetail;
        if($cron_details_obj->start('remove:temporders')) {
            $datetime = Carbon::now()->subHours(6);
            
            $temp_orders = TempOrder::select('id','order_no','is_review_edition','service_id','txn_id','uid')
           // ->where('order_id','!=',0)
           // ->where('payment_by','bluesnap')
            ->where('payment_status','Pending')
            ->where('created_at','<',$datetime)
            ->get();
            
            foreach($temp_orders as $order){
                //Release service purchase review edition count 
                if($order->is_review_edition == 1){
                    $service = Service::select('id','review_edition_count')->find($order->service_id);
                    if($service->review_edition_count > 0){
                        $service->review_edition_count -= 1;
				        $service->save();
                    }
                }

                TempOrderExtra::where('order_id',$order->id)->delete();
                TempCoupanApplied::where('order_id',$order->id)->delete();
                TempAffiliateEarning::where('order_id',$order->id)->delete();

                \Log::info('Deleted Temp Order UID : '.$order->uid);
                \Log::info('Deleted Temp Order Txn ID : '.$order->txn_id);
                $this->info('Order deleted : '.$order->order_no);
                $order->delete();
                
            }
            if(count($temp_orders) == 0){
                $this->info('No any temp order found');
            }
            $cron_details_obj->end('remove:temporders');
        }

    }
}
