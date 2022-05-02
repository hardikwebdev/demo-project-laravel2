<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class sendDataToWickedReports implements ShouldQueue
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
        $ApiKey = env('WICKED_REPORTS_API_KEY');
        //testMode -> for development mode =true,for production mode=false
        $testMode = env('WICKED_REPORTS_TEST_MODE');
        $api = new \WickedReports\WickedReports($ApiKey,$testMode);

        if($this->data['type'] == 'create_user') {
            $all_users = [];
            array_push($all_users,$this->data['users']);
            try {
                $response = $api->addContacts($all_users);
                \Log::channel('wickedreportlog')->info('User Added: '.json_encode($all_users));
                \Log::channel('wickedreportlog')->info('Response for User: '.json_encode($response));
            } catch(\Exception $e) {
                \Log::channel('wickedreportlog')->info('User: '.json_encode($all_users));
                \Log::channel('wickedreportlog')->info('Error for User: '.json_encode($e));
            }
        }

        if($this->data['type'] == 'create_order') {
            /* insert orders */
            $all_orders = [];
            array_push($all_orders,$this->data['orders']);
            try {
                $response = $api->addOrders($all_orders);
                \Log::channel('wickedreportlog')->info('Order Added: '.json_encode($all_orders));
                \Log::channel('wickedreportlog')->info('Response for Order: '.json_encode($response));
            } catch(\Exception $e) {
                \Log::channel('wickedreportlog')->info('Order: '.json_encode($all_orders));
                \Log::channel('wickedreportlog')->info('Error for Order: '.json_encode($e));
            }

            /* insert order_items */
            $all_order_items = $this->data['order_items'];
            try {
                $result_data = $api->addOrderItems($all_order_items);
                \Log::channel('wickedreportlog')->info('Order Items Added: '.json_encode($all_order_items));
                \Log::channel('wickedreportlog')->info('Response for Order Items: '.json_encode($result_data));
            } catch(\Exception $e) {
                \Log::channel('wickedreportlog')->info('Order Items: '.json_encode($all_order_items));
                \Log::channel('wickedreportlog')->info('Error for Order Items: '.json_encode($e));
            }

            /* insert order_payments */
            $all_payments = [];
            array_push($all_payments,$this->data['order_payments']);
            try {
                $result = $api->addOrderPayments($all_payments);
                \Log::channel('wickedreportlog')->info('Order Payment Added: '.json_encode($all_payments));
                \Log::channel('wickedreportlog')->info('Response for Order Payment: '.json_encode($result));
            } catch(\Exception $e) {
                \Log::channel('wickedreportlog')->info('Order Payment: '.json_encode($all_payments));
                \Log::channel('wickedreportlog')->info('Error for Order Payment: '.json_encode($e));
            }
        }
    }
}
