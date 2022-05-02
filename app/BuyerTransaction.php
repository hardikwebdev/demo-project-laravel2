<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class BuyerTransaction extends Model
{
    public $table = 'buyer_transactions';
    
    public function order()
    {
    	return $this->belongsTo('App\Order','order_id','id');	
    }
    public function service_order(){
    	return $this->belongsTo('App\BoostedServicesOrder','order_id','id');	
    }

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
		return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
	}

    public function canRefundCCTransaction(){
        $status = false;
        if(Auth::check() && Auth::user()->parent_id == 0 && $this->cc_refund_status == 0 && $this->status=='add_money_to_wallet' && $this->creditcard_amount > 0 && $this->anount <= Auth::user()->cc_earning){
            $available_check_refund = BuyerTransaction::where('status','add_money_to_wallet')->where('creditcard_amount','>',0)->where('cc_refund_status',0)->where('id','>',$this->id)->sum('creditcard_amount');
            if((Auth::user()->cc_earning - $available_check_refund) >= $this->creditcard_amount){
                $status = true;
            }
        }
        return $status;
    }
}
