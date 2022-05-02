<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SmsHistory extends Model
{
    public static function store_sms_history($country_code){
        
        $country_code = str_replace('+','',$country_code);
        $date = Carbon::now()->subMinute(1);
        $date_for_block = Carbon::now()->subMinute(env('SMS_BLOCK_DURATION'));
        $sms_block = SmsHistory::select('id','total_sent_sms','started_date')
        ->where('country_code',$country_code)
        ->where('started_date','>',$date_for_block)
        ->where('total_sent_sms','>=',env('SMS_LIMITATION'))
        ->first();

        if(!is_null($sms_block)){
            return false;
        }

        $sms_history = SmsHistory::select('id','total_sent_sms','started_date')
        ->where('country_code',$country_code)
        ->where('started_date','>',$date)
        ->first();
        if(!is_null($sms_history)){
            if($sms_history->total_sent_sms >= env('SMS_LIMITATION')){
                return false;
            }
            $sms_history->increment('total_sent_sms', 1);
        }else{
            $sms_history = SmsHistory::select('id','total_sent_sms','started_date')->where('country_code',$country_code)->first();
            if(!$sms_history){
                $sms_history = new SmsHistory;
                $sms_history->country_code = $country_code;
            }
            $sms_history->total_sent_sms = 1;
            $sms_history->started_date = Carbon::now();
            $sms_history->save();
        }

        return true;
    }
}
