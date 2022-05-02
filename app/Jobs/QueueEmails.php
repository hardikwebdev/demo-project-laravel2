<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\SendEmailInQueue;
use App\Models\UnsubscribeEmail;
use App\User;

class QueueEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {    
        $is_email_sent = true;
        if(isset($this->data['receiver_secret']) && isset($this->data['email_type'])){
            $user_id = User::getDecryptedId($this->data['receiver_secret']);
            $unsubscribe_email = UnsubscribeEmail::select('id')->where('email_type',$this->data['email_type'])->where('user_id',$user_id)->where('status',1)->count();
            if($unsubscribe_email){
                $is_email_sent = false;
            }
        }

        if($is_email_sent){    
            $email = new SendEmailInQueue($this->data);
            \Mail::to($this->data['email_to'])->send($email);
        }else{
            return true;
        }
    }
}
