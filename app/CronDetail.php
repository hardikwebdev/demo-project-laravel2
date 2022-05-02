<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CronDetail extends Model
{
    protected $table = 'cron_details';

    public function start($cron_name) {
        $return = false;
        $cron = $this->where('command_name',$cron_name)->first();
        if(!is_null($cron)) {
            if($cron->status == 0) {
                $return = true;
                $cron->status = 1;
                $cron->created_at = time();
                $cron->save();
            }
        } else {
            $return = true;
            $cron = new CronDetail;
            $cron->command_name = $cron_name;
            $cron->status = 1;
            $cron->save();
        }
        return $return;
    }

    public function end($cron_name) {
        $cron = $this->where('command_name',$cron_name)->first();
        if(!is_null($cron)) {
            $cron->status = 0;
            $cron->updated_at = time();
            $cron->save();
        }
        return true;
    }
}
