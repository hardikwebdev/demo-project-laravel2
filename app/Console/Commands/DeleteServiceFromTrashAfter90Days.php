<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Service;
use App\Order;
use App\BoostedServicesOrder;
use App\ServiceMedia;
use App\ServiceQuestion;
use AWS;
use App\CronDetail;

class DeleteServiceFromTrashAfter90Days extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'servicefromtrash:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If service not restored till 90 days, then it will delete from here. This script run daily';

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
        if($cron_details_obj->start('servicefromtrash:delete')) {
            $services = Service::select('id','is_delete','deleted_date')->where('is_delete',1)->whereDate('deleted_date','<=',Carbon::today())->get();
            $this->info(count($services));
            if(count($services) > 0) {
                foreach ($services as $key => $value) {
                    /* $orders = Order::where('service_id', $value->id)->count();
                    $boost_orders = BoostedServicesOrder::where('service_id', $value->id)->count();
                    if ($orders > 0 || $boost_orders > 0) {
                        $value->is_delete = 2;
                        $value->save();
                    } else {
                        Service::where('id', $value->id)->delete();
                    } */

                    //update service as delete
                    $value->is_delete = 2;
                    $value->save();

                    $bucket = env('bucket_service');
                    $ServiceMedia = ServiceMedia::where('service_id', $value->id)->get();
                    if ($ServiceMedia) {
                        foreach ($ServiceMedia as $row) {

                            if ($row->media_type == 'image') {
                                $destinationPath = public_path('/services/images/');
                            } else if ($row->media_type == 'video') {
                                $destinationPath = public_path('/services/video/');
                            } else if ($row->media_type == 'pdf') {
                                $destinationPath = public_path('/services/pdf/');
                            }

                            if (file_exists($destinationPath . $row->media_url) && $row->photo_s3_key == '') {
                                unlink($destinationPath . $row->media_url);
                            } else {
                                $keyData = $row->photo_s3_key;
                                $s3 = AWS::createClient('s3');

                                try {
                                    $result_amazonS3 = $s3->deleteObject([
                                        'Bucket' => $bucket,
                                        'Key' => $keyData,
                                    ]);
                                } catch (Aws\S3\Exception\S3Exception $e) {

                                }

                                //delete thumbnail
                                if($row->thumbnail_media_url != null) {
                                    $thumb_imageKey_ary = explode('/',$row->photo_s3_key);
                                    $keyDataThumb = $thumb_imageKey_ary[0] .'/thumb/'.str_replace(".gif",".png",$thumb_imageKey_ary[1]);
                                    try {
                                        $result_amazonS3 = $s3->deleteObject([
                                            'Bucket' => $bucket,
                                            'Key' => $keyDataThumb,
                                        ]);
                                    } catch (Aws\S3\Exception\S3Exception $e) {
                                        //error
                                    }
                                }
                            }
                        }
                        ServiceMedia::where('service_id', $value->id)->delete();
                        //ServiceQuestion::where('service_id', $value->id)->delete();
                    }
                }
            }
            $cron_details_obj->end('servicefromtrash:delete');
        }
        $this->info('==== Process - end =====');
    }
}
