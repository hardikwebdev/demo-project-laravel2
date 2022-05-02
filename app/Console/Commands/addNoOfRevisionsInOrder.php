<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order;
use App\ServicePlan;

class addNoOfRevisionsInOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revisionsinorder:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add number of revisions in order table for old entries';

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
        $service_plans = ServicePlan::whereNotNull('no_of_revisions')->get();
        foreach ($service_plans as $key => $plan) {
            $orders = Order::where(['service_id'=>$plan->service_id,'plan_type'=>$plan->plan_type])->whereNotIn('status',['completed','cancelled'])->get();
            foreach ($orders as $key => $order) {
                $order->no_of_revisions = $plan->no_of_revisions;
                $order->save();
            }
        }
        $this->info('==== Process - end =====');
    }
}
