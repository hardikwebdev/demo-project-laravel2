<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\CronDetail;

class UpdateSellerLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seller:updatelevel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Seller Profile Level, Run at each 10 second';

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
        if($cron_details_obj->start('seller:updatelevel')) {
            $cDate = date('Y-m-d');
           // $cDate = '2018-07-17';
            $Users = User::select('id','seller_level','seller_level_updated_at')
                ->where('seller_level_updated_at','!=',$cDate)
                //->whereIn('id',[14])
                ->where('status',1)
                ->where('is_delete',0)
                ->where('parent_id',0)
                ->orderBy('updated_at','desc')
                ->paginate(200);
            if(count($Users)){
                foreach ($Users as $value) {
                    $value->seller_level = $value->calculate_base_count($value->id);
                    $value->seller_level_updated_at = $cDate;
                    $value->save();
                   // $this->info($value->seller_level);
                }
                $this->info('Updated '.count($Users).' Records.');
            }else{
                $this->info('All Updated.');
            }
            $cron_details_obj->end('seller:updatelevel');
        }
    }
}
