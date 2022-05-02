<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\CronDetail;
use App\TempFile;
use Carbon\Carbon;

class RemoveUnwantedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:unwantedfiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unwanted files, schedule daily once';

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
        if($cron_details_obj->start('remove:unwantedfiles')) {
            /* Course media temp */
            $path = public_path('courses/media');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('courses/downloadable_files');
            $files = \File::files($path);
            $this->remove_file($files);

            /* Services Temp */
            $path = public_path('services/answers');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('services/files');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('services/images');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('services/pdf');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('services/video');
            $files = \File::files($path);
            $this->remove_file($files);

            /* Seller temp */
            $path = public_path('seller/portfolio');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('seller/level-image');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('seller/profile');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('seller/upload-source-file');
            $files = \File::files($path);
            $this->remove_file($files);

            $path = public_path('seller/upload-work');
            $files = \File::files($path);
            $this->remove_file($files);

            $cron_details_obj->end('remove:unwantedfiles');
        }
    }

    /* Remove unwanted files */ 
    function remove_file($files){
        $current_date = Carbon::now()->subDays(2)->setTimezone('EST');
        foreach ($files as $key => $value) {
            $lastmodified = \DateTime::createFromFormat("U", $value->getMTime());
           
            $lastmodified->setTimezone(new \DateTimeZone('EST'));
            $lastmodified = $lastmodified->format('Y-m-d H:i:s');
            if(strtotime($current_date) > strtotime($lastmodified)){
                unlink($value->getRealPath());
            }
        }
    }
}
