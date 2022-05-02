<div class="row summary mx-0 mt-5">
    <div class="col-12 text-center my-orders cus-container-two table-responsive-sm">  
        <table class="table table-borderless">
            <thead>
                <tr class="manage-sale-head default-td custom-bold-header">
                    <th class="font-12 text-color-4 font-weight-normal text-left min-w-335" width="35%">
                        Service
                    </th>
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">Order Date</th>
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">Due on</th>
                    <th class="font-12 text-color-4 font-weight-normal">Completed Date</th>
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">Total</th>
                    <th class="font-12 text-color-4 font-weight-normal" width="15%">Rating</th>
                </tr>
            </thead>
            <tbody class="border-top-gray order-tab-body">
                @foreach($orders as $row)
                    @php 
                    $order_type = 'Service'; 
                    if($row->service->is_job==1){
                        $order_type = 'Job';
                    }elseif($row->is_course==1){
                        $order_type = 'Course';
                    }elseif($row->service->is_custom_order==1){
                        $order_type = 'Custom Order';
                    }elseif($row->service->is_custom_order==0 && $row->service->is_job==0){
                        $order_type = 'Service';	
                    }
                    @endphp
                    <tr class="clickable">
                        {{-- Order Information --}}
                        <td class="font-14 text-color-6 text-left">
                            <div>
                                <div class="font-13 font-weight-bold">
                                    <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('buyer_orders_details',$row->order_no)}}">{{$row->service->title}}</a>
                                </div>
                                <div>
                                    <span class="font-12 text-color-4">
                                        <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('buyer_orders_details',$row->order_no)}}">#{{$row->order_no}} </a>
                                        &nbsp;|&nbsp; {{$order_type}} from
                                    </span>
                                    <a href="{{route('viewuserservices',$row->seller->username)}}">
                                        <span class="text-color-1 font-12 pl-1">{{ ucfirst($row->seller->username) }}</span> 
                                    </a>
                                </div>
                            </div>
                        </td>
                        {{-- Order Date --}}
                        <td class="font-13 text-color-6">
                            <div class="v-just-same">
                                @if($row->start_date)
                                    {{date('M d Y',strtotime($row->start_date))}}
                                @else
                                -
                                @endif
                            </div>
                        </td>
                        {{-- Order Due Date --}}
                        <td class="font-13 text-color-6">
                            <div class="v-just-same">
                                @if($row->status != 'new')
                                @if($row->end_date && $row->service->is_recurring == 0)
                                {{date('M d Y',strtotime($row->end_date))}} at {{date('h:i A',strtotime($row->end_date))}}
                                @if(count($row->order_extend_requests) && $row->status=='active')
                                <a href="{{route('buyer_extended_order_request',$row->order_no)}}">
                                    <span class="fa fa-exclamation-circle" title="Pending Approval"></span>
                                </a>
                                @endif
                                @else
                                -
                                @endif
                                @else
                                -
                                @endif
                            </div>
                        </td>
                        {{-- Completed Date --}}
                        <td class="text-center text-color-6">
                            @if($row->status == "completed")
                                {{date('M d Y',strtotime($row->completed_date))}} at {{date('h:i A',strtotime($row->completed_date))}}
                            @else
                            -
                            @endif
                        </td>
                        {{-- Price --}}
                        <td class="font-13 text-color-6 font-weight-bold">
                            <div class="v-just-same">
                                ${{$row->order_total_amount}}
                            </div>
                        </td>
                        {{-- Rating --}}
                        <td class="pl-0">
                            @if($row->status == 'completed')
                            <div class="cursor-pointer first_div" data-order="{{$row->secret}}">
                                <i class="fa fa-star @if($row->seller_rating>=1)star_checked @else star_unchecked @endif"></i>
                                <i class="fa fa-star @if($row->seller_rating>=2)star_checked @else star_unchecked @endif"></i>
                                <i class="fa fa-star @if($row->seller_rating>=3)star_checked @else star_unchecked @endif"></i>
                                <i class="fa fa-star @if($row->seller_rating>=4)star_checked @else star_unchecked @endif"></i>
                                <i class="fa fa-star @if($row->seller_rating>=5)star_checked @else star_unchecked @endif"></i>
                            </div>
                            @endif
                            @if($row->status == 'delivered')
                            <a class="order-btn btn btn_complete_order" data-url="{{route('complete_order',[$row->id])}}" data-is_recurring="{{$row->is_recurring}}" href="javascript:void(0);">Complete & Review</a>
                            @else
                            <a class="text-color-1 font-14 skip-order-rating" data-url="{{route('order_skip_rating',$row->secret)}}" href="Javascript:;">
                                Skip Review
                            </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if(count($orders)==0)
                <tr>
                    <td colspan="9" class="text-center">
                        No order found
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
        <div class="text-center mt-3 order-pagination text-white">
            @if(isset($orders))
                {{ $orders->appends(['search' => isset($_GET['search'])?$_GET['search']:'', 'status' => isset($_GET['status'])?$_GET['status']:'', ])->links("pagination::bootstrap-4") }}
            @endif
        </div>
    </div>
</div>