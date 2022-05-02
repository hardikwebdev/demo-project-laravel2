<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order;

class AddCancelOrderLateReviewToSeller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancelorder:review';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add review 1 on cancel and late order';

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
        $Orders = Order::select('cancel_date','order_no','id')->where('status', 'cancelled')
            ->where("is_review",1)
            ->where("seller_rating",1)
            ->get();

        if(count($Orders) > 0){
            foreach ($Orders as $Order) {
                $Order->review_date = $Order->cancel_date;
                $Order->save();
                $this->info('Oder #'.$Order->order_no);
            }
        }
    }
}
