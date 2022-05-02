<?php

namespace App\Http\Controllers\Api\V1;

use App\AffiliateEarning;
use App\BuyerTransaction;
use App\EmailTemplate;
use App\Http\Controllers\Controller;
use App\Service;
use App\Specialaffiliatedusers;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Order_review_log;
use App\Notification;
use App\MessageDetail;
use App\Message;
use App\Order;
use App\User;
use Auth;
use Edujugon\PushNotification\PushNotification;
use Mail;
use Validator;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;

class OrderController extends Controller
{
    private $uid;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            $this->uid_secret = Auth::user()->secret;
            if (Auth::user()->parent_id != 0) {
                $this->uid = Auth::user()->parent_id;
                $parentUser = User::select('id')->find(Auth::user()->parent_id);
                $this->uid_secret = $parentUser->secret;
            }
            return $next($request);
        });
    }

    public function buyerOrders(Request $request)
    {

        $offset = 0;
        $limit = 10;
        $uid = $this->uid;

        if ($uid == '') {
            return response([
                "success" => false,
                "message" => "Something went wrong.",
                "code" => 400
            ], 400);
        }
        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        $Orders = Order::with(['seller' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'user' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'service' => function ($q) {
            $q->select('id', display_title('title'));
        }])->select('id', 'order_no', 'seller_uid', 'order_total_amount as price', 'uid', 'service_id', 'start_date', 'end_date', 'status', 'is_review', 'seller_rating','order_note','is_job','is_custom_order')
            ->Where('uid', $uid)
            ->where('is_course',0);
        $Orders = $Orders->orderBy('id', 'desc')->offset($offset)->limit($limit)->get()->append('OrderStatus')
            ->each(function ($items) {
                $items->service->append('ServiceTitle');
            });
        
        foreach ($Orders as $key => $value) {
            $value->order_type = 'Service';
            if($value->is_custom_order == 1) {
                $value->order_type = 'Custom order';
            } else if($value->is_job == 1) {
                $value->order_type = 'Job';
            }
        }

        return response([
            "success" => true,
            "message" => "",
            "orders" => $Orders,
            "code" => 200
        ], 200);

    }

    public function sellerOrders(Request $request)
    {

        $limit = 10;
        $offset = 0;
        $uid = $this->uid;

        if ($uid == '') {
            return response([
                "success" => false,
                "message" => "Something went wrong.",
                "code" => 400
            ], 400);
        }

        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        $Orders = Order::with(['seller' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'user' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'service' => function ($q) {
            $q->select('id', 'title');
        }])->select('id', 'order_no', 'seller_uid','order_total_amount as price', 'uid', 'service_id', 'start_date','end_date', 'status', 'order_note', 'is_review','seller_rating','is_job','is_custom_order')
            ->where('seller_uid', $uid)
            ->where('is_course',0);
        $Orders = $Orders->orderBy('id', 'desc')->offset($offset)->limit($limit)->get()->append('OrderStatus')
            ->each(function ($items) {
                $items->service->append('ServiceTitle');
            });

        foreach ($Orders as $key => $value) {
            $value->order_type = 'Service';
            if($value->is_custom_order == 1) {
                $value->order_type = 'Custom order';
            } else if($value->is_job == 1) {
                $value->order_type = 'Job';
            }
        }

        return response([
            "success" => true,
            "message" => "",
            "orders" => $Orders,
            "code" => 200
        ], 200);

    }

    public function completeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'order_no' => 'required|exists:orders,order_no',
        ), [
            'order_no.required' => "The order no field is required",
            'order_no.exists' => "Please enter valid order no",
        ]);

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        if ($request->input()) {

            return $this->orderComplete($request);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Something goes wrong',
                'code' => 400
            ], 400);
        }
    }

    public function completeOrderWithReview(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'order_no' => 'required|exists:orders,order_no',
            'seller_rating' => 'required|numeric|min:1|max:5',
            'complete_note' => 'required'
        ), [
            'order_no.exists' => "Please enter valid order no",
        ]);

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        if ($request->input()) {

            return $this->orderComplete($request);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Something goes wrong',
                'code' => 400
            ], 400);
        }
    }

    public function show_order(Request $request)
    {

        if (!$request->has('order_id') || $request->order_id == '') {
            return response([
                "success" => false,
                "message" => "Something went wrong.",
                "code" => 400
            ], 400);
        }

        $uid = $this->uid;
        $order_no = $request->order_id;

        $Order = Order::select('id', 'seller_uid', 'price', 'order_no', 'uid', 'service_id')
            ->where(['order_no' => $order_no])
            ->where(function ($q) use ($uid) {
                $q->where('uid', $uid);
                $q->orWhere('seller_uid', $uid);
            });
    
        $Order = $Order->with(['seller:id,Name,username,profile_photo', 'service:id,title', 'order_revisions:order_id,description,delivered_note'/* ,
            'attachement:order_id,filename,photo_s3_key' */])->first();

        if(!is_null($Order)) {
            return response([
                "success" => true,
                "message" => "",
                "order_details" => $Order,
                "code" => 200
            ], 200);
        }

        return response([
            "success" => false,
            "message" => "Invalid order ID.",
            "code" => 400
        ], 400);
    }

    public function addOrderReview(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'order_no' => 'required|exists:orders,order_no',
            'seller_rating' => 'required|numeric|min:1|max:5',
            'complete_note' => 'required'
        ), [
            'order_no.exists' => "Please enter valid order no",
        ]);

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        $uid = $this->uid;
        if ($uid == '') {
            return response([
                "success" => false,
                "message" => "Something went wrong.",
                "code" => 400
            ], 400);
        }

        $cdate = date('Y-m-d H:i:s');
        $order_no = $request->order_no;
        $order = $Order = Order::with(['seller' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'user' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'service' => function ($q) {
            $q->select('id', 'title');
        }])->select('id', 'order_no', 'seller_uid',
            'price', 'uid', 'service_id', 'start_date',
            'end_date', 'status', 'order_note', 'is_review',
            'seller_rating','created_at')
            ->where('order_no', $order_no)
            ->where('uid', $uid)
            ->where('status', 'completed');

        $order = $order->first();

        if ($order != null) {
            if ($order->seller_rating == 0 && $order->completed_note == null) {
                $review_log = new Order_review_log;
                $review_log->order_id = $order->id;
                $review_log->log = json_encode(array("review" => $request->complete_note, "review_date" => $cdate,
                    "seller_rating" => $request->seller_rating));
                $review_log->save();

                $order->is_review = 1;
                $order->seller_rating = $request->seller_rating;
                $order->completed_note = $request->complete_note;
                $order->review_date = $cdate;
                $order->save();

                /* send email */
                if($order->seller_rating > 0){
                    $seller = User::select('id','username','email')->find($order->seller_uid);
                    $buyer = User::select('id','username','email')->find($order->uid);

                    $data = [
                        'receiver_secret' => $seller->secret,
				        'email_type' => 1,
                        'username' => $seller->username,
                        'orderNumber' => $order->order_no,
                        'orderDetail' => $order,
                        'name' => $buyer->username,
                        'order_detail_url' => url('seller/orders/details/' . $order->order_no),
                        'seller_rating' => $order->seller_rating,
                        'complete_note' => $order->completed_note,
                        'email_to' => $seller->email,
                        'subject' => 'You Have A New Review From Buyer!',
                        'template' => 'frontend.emails.v1.review_email',
                    ];
                    /* send email */
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));
                }
                
                $order = $order->append('OrderStatus');

                return response([
                    "success" => true,
                    "message" => "Review has been added successfully.",
                    'order' => $order,
                    "code" => 200
                ], 200);
            } else {
                return response([
                    "success" => false,
                    "message" => "Your review already exists",
                    "code" => 400
                ], 400);
            }
        }

        return response([
            "success" => false,
            "message" => "Something went wrong.",
            "code" => 400
        ], 400);
    }

    /**
     * @param Request e$request
     * @return JsonResponse
     */
    //Common Order complete function
    function orderComplete(Request $request)
    {
        $specialAffiliateFlag = 0;
        $affiliate_per = 15;
        $uid = Auth::user()->id;
        $cdate = date('Y-m-d H:i:s');

        $Order = $Order = Order::with(['seller' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'user' => function ($q) {
            $q->select('id', 'Name', 'username');
        }, 'service' => function ($q) {
            $q->select('id', 'title');
        }])->select('id', 'order_no', 'seller_uid',
            'price', 'uid', 'service_id', 'start_date',
            'end_date', 'status', 'order_note', 'is_review',
            'seller_rating')
            ->where(['uid' => $uid, 'order_no' => $request->order_no, 'status' => 'delivered','is_course' => 0]);
        $Order = $Order->first();

        if (empty($Order)) {
            //return redirect(route('buyer_orders'));
            return response()->json([
                'success' => false,
                'message' => 'Something goes wrong',
                'code' => 400
            ], 400);
        }

        /*If Order is Paused then Not able to complete that*/
        if ($Order->is_pause == 1) {
            //\Session::flash('tostError', 'Your order has been blocked! Contact administrator for further details!');
            return response()->json([
                'success' => false,
                'message' => 'Your order has been blocked! Contact administrator for further details!',
                'code' => 400
            ], 400);
            //return redirect(route('buyer_orders'));
        }


        if ($request->input()) {
            $services = Service::select('id','total_review_count','service_rating')->find($Order->service_id);
            /* update seller wallet amount - services charge 5% */
            $extra = 0;
            if (!empty($Order->extra)) {
                foreach ($Order->extra as $row) {
                    $extra += $row->qty * $row->price;
                }
            }
            /* ======== Special Affiliate ============== */
            if ($Order->is_affiliate == "1") {
                /* ===== Special Affiliate ========= */
                $Affiliate = AffiliateEarning::where(['status' => 'pending_clearance', 'order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
                if (!empty($Affiliate)) {
                    $specialAffiliatedUser = Specialaffiliatedusers::where('uid', $Affiliate->affiliate_user_id)->first();
                    if ($specialAffiliatedUser != null) {
                        $specialAffiliateFlag = 1;
                    }
                }
            }
            /* =========== End ============ */

            $product_price = (($Order->price * $Order->qty) + $extra) - $Order->reorder_discount_amount - $Order->coupon_discount - $Order->volume_discount - $Order->combo_discount;

            /*For recurring service*/
            if ($Order->is_recurring == 1) {

                app('App\Http\Controllers\PaypalPaymentController')->cancelPremiumOrder($Order->subscription->profile_id);

                $profile_receipt = json_decode($Order->subscription['receipt'], true);
                /*For Second recurring service*/

                if ($profile_receipt['NUMCYCLESCOMPLETED'] != "0") {
                    /*Servie Price * qty + Extra*/
                    $findBuyerTransaction = BuyerTransaction::where('order_id',$Order->id)->select('id','payment_processing_fee')->first();
                    if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
                        $product_price = $profile_receipt['AMT'] - $findBuyerTransaction->payment_processing_fee; 
                    } else {
                        $product_price = $profile_receipt['AMT']; 
                    }
                }

            }

            /*begin : get admin,jim service charge*/
            $serviceCharge = get_service_change($product_price, $Order->is_new);
            $serviceChargeJim = get_jim_service_change($product_price, $Order->is_new);
            /*end : get admin,jim service charge*/

            if ($specialAffiliateFlag == 0) {

            } else {
                $serviceCharge = 0;
                $affiliate_per = 25;
            }

            /*For special seller*/
            if ($Order->is_special_order == 1) {
                $serviceCharge = 0;
            }

            $Order->status = 'completed';
            $Order->service_charge = $serviceCharge;
            $Order->completed_date = $cdate;

            if ($request->seller_rating) {
                $Order->is_review = 1;
                $Order->review_date = $cdate;
                $services->total_review_count = $services->total_review_count + 1;
            } else {
                $Order->is_review = 0;
            }

            $seller = User::find($Order->seller_uid);

            if (trim($request->complete_note)) {
                $Order->completed_note = $request->complete_note;
            }
            if($request->seller_rating) {
                $Order->seller_rating = $request->seller_rating;
            }
            $Order->save();

            //Update last child recurrence (complete order process)
            if ($Order->is_recurring == 1) {
                app('App\Http\Controllers\PaypalPaymentController')->updateLastChildRecurrence($Order);
            }

            /*check_first_five_start*/
            $check_first_five_start = Order::where('seller_uid', $Order->seller_uid)->where('seller_rating', 5)->count();
            if ($check_first_five_start == 1) {
                $data = [
                    'receiver_secret' => $seller->secret,
                    'email_type' => 1,
                    'firstname' => $seller->Name,
                    'email_to' => $seller->email,
                    'subject' => 'You just rocked it',
                    'template' => 'frontend.emails.v1.first_five_star_review',
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));
            }
            /*check_first_five_start*/

            if($Order->is_review == 1 && (isset($request->complete_note) || isset($request->seller_rating))) {
                $review_log = new Order_review_log;
                $review_log->order_id = $Order->id;
                $review_log->log = json_encode(array("review" => $request->complete_note, "review_date" => $cdate, "seller_rating" => $request->seller_rating));
                $review_log->save();

                /* Send Review Email To Seller */
                $seller_rating = $request->seller_rating;
                $complete_note = $request->complete_note;

                $seller = User::find($Order->seller_uid);
                $buyer = User::find($Order->uid);
                $orderDetail = Order::with('service', 'user', 'seller', 'extra')->where('order_no', $Order->order_no)->first();

                $data = [
                    'receiver_secret' => $seller->secret,
                    'email_type' => 1,
                    'username' => $seller->username,
                    'orderNumber' => $Order->order_no,
                    'orderDetail' => $orderDetail,
                    'name' => $buyer->username,
                    'order_detail_url' => url('seller/orders/details/' . $Order->order_no),
                    'seller_rating' => $seller_rating,
                    'complete_note' => $complete_note,
                    'email_to' => $seller->email,
                    'subject' => 'You Have A New Review From Buyer!',
                    'template' => 'frontend.emails.v1.review_email',
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));
            }

            $earning = $product_price - $serviceCharge;
            $User = User::find($Order->seller_uid);
            if ($Order->is_affiliate == "1") {
                $affiliate_income = ($product_price * $affiliate_per) / 100;
                $User->freeze = $User->freeze + ($earning - $affiliate_income);
                $User->net_income = $User->net_income + ($earning - $affiliate_income);
            } else {
                $User->freeze = $User->freeze + $earning;
                $User->net_income = $User->net_income + $earning;
            }
            $User->save();

            /* Buyer Transactions Start */
            $buyerTransaction = new BuyerTransaction;
            $buyerTransaction->order_id = $Order->id;
            $buyerTransaction->buyer_id = $Order->uid;
            $buyerTransaction->note = 'Payment for Service Purchase';
            $buyerTransaction->anount = $product_price;
            $buyerTransaction->status = 'payment';
            $buyerTransaction->created_at = time();
            $buyerTransaction->save();
            /* Buyer Transactions End */

            /* Send Notification to buyer Start */
            $notify_from = $uid;
            if ($notify_from == $Order->uid) {
                $notify_to = $Order->seller_uid;
            } elseif ($notify_from == $Order->seller_uid) {
                $notify_to = $Order->uid;
            }

            $notification = new Notification;
            $notification->notify_to = $notify_to;
            $notification->notify_from = $notify_from;
            $notification->notify_by = 'buyer';
            $notification->order_id = $Order->id;
            $notification->is_read = 0;
            $notification->type = 'complete_order';
            $notification->message = 'Order #' . $Order->order_no . ' has completed';
            $notification->created_at = time();
            $notification->updated_at = time();
            $notification->save();
            /* Send Notification to buyer End */

            if($Order->is_recurring == 0){
				//Update payment date on seller earning
				Order::storeSellerEarningPaymentDate($Order);
			}

            $objOrder = new Order;
            $avgRating = $objOrder->calculateServiceAverageRating($Order->service_id);
            $services->service_rating = $avgRating;
            $services->save();

            $Order->makeOrderOnHoldToActive($services);

            /* Send Email to Seller */
            $orderDetailTable = $Order->get_order_detail($Order->order_no);
            $orderDetail = Order::with('service', 'user', 'seller', 'extra')->where('order_no', $Order->order_no)->get();

            $template = EmailTemplate::find(4);
            $seller = User::find($Order->seller_uid);
            $buyer = User::find($Order->uid);

            $data = [
                'receiver_secret' => $seller->secret,
                'email_type' => 1,
                'username' => $seller->username,
                'orderNumber' => $Order->order_no,
                'orderDetail' => $orderDetail,
                'name' => $buyer->username,
                'order_detail_url' => url('seller/orders/details/' . $Order->order_no),
                'email_to' => $seller->email,
				'subject' => 'Your order has been completed',
				'template' => 'frontend.emails.v1.complete_order',
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

            $Order = $Order->append('OrderStatus');

            return response()->json([
                'success' => true,
                'message' => 'Your order has been completed',
                'orders' => $Order,
                'code' => 200
            ], 200);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Something goes wrong',
                'code' => 400
            ], 400);
        }
    }

    public function get_order_detail($order_id) {
        $Order = Order::with(['seller' => function ($q) {
                        $q->select('id', 'Name', 'username');
                    }, 'user' => function ($q) {
                        $q->select('id', 'Name', 'username');
                    }, 'service' => function ($q) {
                        $q->select('id', display_title('title'));
                    }])
                ->select('id', 'order_no', 'seller_uid', 'price', 'uid', 'service_id', 'start_date', 'end_date', 'status', 'is_review', 'seller_rating')
                ->Where('id', $order_id)
                ->first()
                ->append('OrderStatus');
        return $Order;
    }
}
