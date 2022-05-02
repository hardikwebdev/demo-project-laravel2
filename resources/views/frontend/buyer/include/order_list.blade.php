@php
use App\Commands\SortableTrait;
@endphp
<div class="row summary mx-0 mt-5">
    <div class="col-12 text-center my-orders cus-container-two table-responsive">  
        <table class="table table-borderless">
            <thead>
                <tr class="manage-sale-head default-td custom-bold-header">
                    <th class="font-12 text-color-4 font-weight-normal text-left min-w-335" width="35%">{{ SortableTrait::link_to_sorting_action('title','Service')}}</th>
                    {{-- Only for Buyer  --}}
                    @if(!isset($is_seller_order_list))
                    <th class="font-12 text-color-4 font-weight-normal" width="20%">Tags</th>
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">{{ SortableTrait::link_to_sorting_action('created_by','Created By')}}</th>
                    @endif
                    {{-- END Only for Buyer  --}}
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">{{ SortableTrait::link_to_sorting_action('start_date','Order Date')}}</th>
                    
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">{{ SortableTrait::link_to_sorting_action('end_date','Due on')}}</th>

                    @if(isset($_GET['status']) && $_GET['status']=='delivered' && $_GET['ordertype'] != 'course')
                    <th class="font-12 text-color-4 font-weight-normal">Delivered At</th>
                    @endif
                    @if(!isset($is_seller_order_list) || isset($is_seller_order_list) && Auth::user()->parent_id == 0)
                    <th class="font-12 text-color-4 font-weight-normal" width="10%"> @if(!isset($is_seller_order_list)) Price @else Total @endif</th>
                    @endif
                    {{-- Only for Seller  --}}
                    @if(isset($is_seller_order_list) && $is_seller_order_list == 1)
                    <th class="font-12 text-color-4 font-weight-normal" width="5%">Affiliate</th>
                    @if(isset($_GET['ordertype']) && $_GET['ordertype'] != 'course' || !isset($_GET['ordertype']))
                    <th class="font-12 text-color-4 font-weight-normal" width="5%">{{ SortableTrait::link_to_sorting_action('order_note','Buyer Note')}}</th>
                    @endif
                    @endif
                    {{-- END Only for Seller  --}}
                    <th class="font-12 text-color-4 font-weight-normal" width="10%">{{ SortableTrait::link_to_sorting_action('status','Status')}}</th>
                    <th class="font-12 text-color-4 font-weight-normal pl-0 pr-0" width="4%">Invoice</th>
                </tr>
            </thead>
            <tbody class="border-top-gray order-tab-body">
                @foreach($Order as $row)
                
                    @php
                    $readClass = '';
                    if($row->is_seen != '0') {
                        $readClass ="read_order";
                    }
                    $childOrders = $row->child;
                    @endphp
                    
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
                        <td class="font-14 text-color-6 text-left pl-0">
                            <div class="d-flex align-items-center">
                                <div>
                                    @if(count($childOrders))
                                    <div data-toggle="collapse" id="order_{{$row->id}}" data-target=".order_{{$row->id}}" class="hideshowicon mr-1"><i class="fa fa-plus-circle"></i></div>
                                    @else
                                    <div class="hideshowicon mr-3">&nbsp;</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-13 font-weight-bold">
                                        @if(!isset($is_seller_order_list))
                                            @if($row->is_course == 1)
                                                <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('course_details',[$row->seller->username,$row->service->seo_url])}}">{{$row->service->title}} </a>
                                            @else
                                                <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('buyer_orders_details',$row->order_no)}}">{{$row->service->title}} </a>
                                            @endif
                                        @else
                                            <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('seller_orders_details',$row->order_no)}}">{{$row->service->title}}</a>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-12 text-color-4">
                                            @if($row->is_course == 1)
                                                @if(!isset($is_seller_order_list))
                                                    <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('course_details',[$row->seller->username,$row->service->seo_url])}}">#{{$row->order_no}} </a>
                                                @else
                                                    <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('seller_orders_details',$row->order_no)}}">#{{$row->order_no}} </a>
                                                @endif
                                            @else
                                                @if(!isset($is_seller_order_list))
                                                    @if($row->status == 'new')
                                                        @if(Auth::user()->check_sub_user_permission('can_start_order'))
                                                            <a class="text-color-4 view-order-detail-btn"target="_blank" href="{{route('order_submit_requirements',$row->order_no)}}">#{{$row->order_no}} </a>
                                                        @else
                                                            <span class="text-color-4">#{{$row->order_no}}</span>
                                                        @endif
                                                    @else
                                                        <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('buyer_orders_details',$row->order_no)}}">#{{$row->order_no}} </a>
                                                    @endif
                                                @else
                                                    <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('seller_orders_details',$row->order_no)}}">#{{$row->order_no}} </a>
                                                @endif
                                            @endif

                                        &nbsp;|&nbsp; {{$order_type}} from</span>

                                        @if(!isset($is_seller_order_list))
                                        <a href="{{route('viewuserservices',$row->seller->username)}}">
                                            <span class="text-color-1 font-12 pl-1">{{ ucfirst($row->seller->username) }}</span> 
                                        </a>
                                        @else
                                        <a href="{{route('viewuserservices',$row->user->username)}}">
                                            <span class="text-color-1 font-12 pl-1">{{ ucfirst($row->user->username) }}</span> 
                                        </a>
                                        @endif

                                        @if($row->message && $row->message->messageDetail && $parent_uid == $row->message->messageDetail->last()->to_user)
                                        @if($row->message->messageDetail->last()->is_read == 0)
                                            <span class="ml-2">
                                                <i class="fa fa-circle icon-blue"></i>
                                            </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        {{-- Tag --}}
                        @if(!isset($is_seller_order_list))
                        <td id="tag_section{{$row->id}}">
                            <div class="d-flex flex-wrap">
                                <div class="dropdown add_tag_dropdown">
                                    <button class="btn bg-primary-blue text-white font-10 font-weight-bold px-2 py-1 mr-1 mt-1 add-tag dropdown-toggle"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-plus mr-1"></i> Add Tag</button>

                                    <div class="row mt-2 justify-content-center bg-white shadow tag-input border-radius-6px py-4 px-2 dropdown-menu">
                                        <div class="col-12">
                                            <p class="text-color-6 font-14 font-weight-bold text-left">Add Tag</p>
                                            <div class="d-flex flex-wrap" id="tag_list_area{{$row->id}}">
                                                @foreach ($MostUsedOrderTags as $item)
                                                    <a href="javascript:void(0)" data-orderid="{{$row->id}}" data-tagid="{{$item->secret}}" class="add_tag_in_order_btn">
                                                        <span class="@if(in_array($item->id,$row->added_tags)) bg-primary-blue text-white @else bg-dark-white text-color-6 @endif border-gray-1px border-radius-3px font-weight-bold font-10 px-2 py-1 mr-1 mt-1 cursor-pointer tag_break">
                                                            {{$item->tag_name}} 
                                                        </span>
                                                    </a>
                                                @endforeach
                                            </div>
                                            <div class="mt-3">
                                                    {{-- <input class="form-control mr-sm-2 tm-input" id="tags{{$row->id}}" name="tags" type="search" placeholder="Search"> --}}
                                                <form class="">
                                                    <div class="row">
                                                        <div class="col-8 search_drop_padding">
                                                            <select name="search_service_term" class="search_tag" name="tags" id="search_tag{{$row->id}}">
                                                                <option value="">Search</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-4">
                                                            <button class="bg-primary-blue text-white border-radius-6px border-0 font-weight-bold font-14 py-1 px-3 mt-3 search_tag_btn" type="button" data-orderid="{{$row->id}}">Add</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- added tag list - start --}}
                                @foreach ($row->taglist as $index => $value)
                                    @if($index < 5)
                                    <span class="bg-dark-white border-gray-1px border-radius-3px text-color-6 font-weight-bold font-10 px-2 py-1 mr-1 mt-1 cursor-pointer tag_break">
                                        {{$value->tagname->tag_name}} 
                                        <a href="javascript:void(0)" class="remove_tag_from_order_btn" data-orderid="{{$row->secret}}" data-tagid="{{$value->tag_id}}"><i class="fas fa-times ml-2 text-color-6"></i></a>
                                    </span>
                                    @endif
                                @endforeach
                                @if(count($row->taglist) > 5)
                                <div class="dropdown mt-1 clear_tag_dropdown">
                                    <span class="bg-dark-white border-gray-1px border-radius-3px text-color-6 font-weight-bold font-10 px-2 py-1 mr-1 mt-1 cursor-pointer  clear-tags dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">...</span>

                                    <div class="row justify-content-center bg-white shadow tag-list border-radius-6px py-4 px-2 dropdown-menu mt-3">
                                        <div class="col-12">
                                            <p class="text-color-6 font-12 font-weight-bold text-left"><span id="total_added_tag_counter{{$row->secret}}">{{count($row->taglist)}}</span> Tags</p>
                                            <div class="d-flex flex-wrap">
                                                @foreach ($row->taglist as $index => $value)
                                                    {{-- @if($index >= 5) --}}
                                                    <span class="bg-dark-white border-gray-1px border-radius-3px text-color-6 font-weight-bold font-10 px-2 py-1 mr-1 mt-1 cursor-pointer tag_break" id="added_tag_span{{$value->tag_id}}">
                                                        {{$value->tagname->tag_name}} 
                                                        <a href="javascript:void(0)" class="remove_added_tag_from_order_btn" data-orderid="{{$row->secret}}" data-tagid="{{$value->tag_id}}"><i class="fas fa-times ml-2"></i></a>
                                                    </span>
                                                    {{-- @endif --}}
                                                @endforeach
                                            </div>
                                            <div class="text-right">    
                                                <a href="javascript:void(0)" class="text-color-1 font-weight-bold font-12 mb-0 clear_all_tags_from_order_btn" data-orderid="{{$row->secret}}">Clear All</a>                                        
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                {{-- added tag list - end --}}

                                
                            </div>
                        </td>
                        <td class="font-13 text-color-6 text-center pl-0 capitalize-letter">
                            {{ $row->created_by_user->Name }}
                        </td>
                        @endif
                        {{-- Order Date --}}
                        <td class="font-13 text-color-6">
                            <div class="v-just-same">
                                @if($row->start_date)
                                    {{date('M d Y',strtotime($row->start_date))}}
                                    @if($row->is_recurring == 1)
                                        <br> <span class="recurring-text">STARTED</span>
                                    @endif
                                @else
                                -
                                @endif
                            </div>
                        </td>
                        {{-- Order Due Date --}}
                        <td class="font-13 text-color-6">
                            <div class="v-just-same">
                            @if($row->status != 'new' && $row->status != 'on_hold')
                                @if($row->is_course == 1 && $row->is_recurring == 0)
                                -
                                @elseif($row->end_date)
                                    {{date('M d Y',strtotime($row->end_date))}} at {{date('h:i A',strtotime($row->end_date))}}
                                    @if($row->is_recurring == 1 && !in_array($row->status,['cancelled','completed']))
                                    <br> <span class="recurring-text">NEXT RENEWAL</span>
                                    @endif
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
                       
                        {{-- Delivered Date --}}
                        @if(isset($_GET['status']) && $_GET['status']=='delivered' && $_GET['ordertype'] != 'course')
                            <td class="text-center">
                                @if($row->delivered_date)
                                {{date('d M Y',strtotime($row->delivered_date))}}
                                @else
                                -
                                @endif
                            </td>
                        @endif
                        {{-- Price --}}
                        @if(!isset($is_seller_order_list) || isset($is_seller_order_list) && Auth::user()->parent_id == 0)
                            <td class="font-13 text-color-6 font-weight-bold">
                                <div class="v-just-same">
                                ${{$row->order_total_amount}}
                                @if($row->is_recurring == 1)
                                <br> <span class="recurring-text">TOTAL</span>
                                @endif
                                </div>
                            </td>
                        @endif

                        {{-- Only for Seller  --}}
                        @if(isset($is_seller_order_list) && $is_seller_order_list == 1)
                        {{-- Affiliate --}}
                        <td class="font-13 text-color-6">
                            @if($row->is_affiliate == 1) Yes @else No @endif
                        </td>
                        {{-- Buyer Note --}}
                        @if(isset($_GET['ordertype']) && $_GET['ordertype'] != 'course' || !isset($_GET['ordertype']))
                        <td class="font-13 text-color-6">
                            @if(!empty($row->order_note))
                                <button class="view-invoid-btn open-new-message text-color-4" data-target="#order-note-model-{{$row->id}}" data-toggle="modal"  title="Buyer Note"><i class="fa fa-sticky-note" aria-hidden="true"></i></button>
                                <div id="order-note-model-{{$row->id}}" role="dialog" class="modal fade new-message mfp-hide custommodel" aria-hidden="true">
                                    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Order Note</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group bold-lable">
                                                    <div class="form-group m-form__group row">
                                                        <div class="col-lg-12">
                                                            <?php echo $row->order_note;?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <label> - </label>
                            @endif
                        </td>
                        @endif
                        @endif

                        {{-- Status --}}
                        <td class="font-10 text-white">
                            @if($row->is_recurring == 1 && !in_array($row->status,['new','completed','cancelled']))
                            <span class="lbl_is_recurring">RECURRING</span>
                            @else
                                @if(isset($_GET['status']) && $_GET['status']=='late')
                                    <span class="bg-danger  py-1 px-2 border-radius-8px">LATE</span>
                                    @elseif(isset($_GET['status']) && $_GET['status']=='active')
                                    <span class="bg-primary-blue py-1 px-2 border-radius-8px">IN PROGRESS</span>
                                    @else
                                @if($row->status=='active')
                                    @php
                                    $cdate = date('Y-m-d H:i:s');
                                    @endphp
                                    @if($row->delivered_date == null && $row->end_date < $cdate && $row->is_recurring == 0)
                                        <span class="bg-danger  py-1 px-2 border-radius-8px">LATE</span>
                                    @else
                                        <span class="bg-primary-blue py-1 px-2 border-radius-8px">IN PROGRESS</span>
                                    @endif
                                @elseif($row->status == 'cancelled')
                                <span class="bg-light-gray py-1 px-2 border-radius-8px">{{strtoupper($row->status)}}</span>
                                @elseif($row->status == 'in_revision')
                                <span class="in_revision">IN REVISION</span>
                                @elseif($row->status == 'on_hold')
                                <span class="rejected">ON HOLD</span>
                                @elseif($row->status == 'new')
                                <span class="bg-warning py-1 px-2 border-radius-8px">INCOMPLETE</span>
                                @else
                                <span class="bg-green py-1 px-2 border-radius-8px">{{strtoupper($row->status)}}</span>
                                @endif
                                @endif  
                            @endif
                        </td>
                        {{-- Invoice --}}
                        <td class="pl-0">
                            @if($row->status == 'completed' && $row->order_total_amount != 0)
                            <form action="{{ route('view_order_invoice')}}" method="GET" target="_blank">
                                @if(!isset($is_seller_order_list) && Auth::user()->parent_id == 0)
                                <input type="hidden" name="hash" value="{{ $row->get_invoice_url($row,1) }}">
                                @else
                                <input type="hidden" name="hash" value="{{ $row->get_invoice_url($row) }}">
                                @endif
                                <button class="view-invoid-btn text-color-4" type="submit" title="View Invoice"><i class="far fa-file-alt"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>

                    {{-- Child Order List --}}
                    @if(count($childOrders))
                    @foreach($childOrders as $child)
                    <tr class="collapse order_{{$row->id}}">
                        {{-- Order Information --}}
                        <td class="font-14 text-color-6 text-left pl-3" @if(!isset($is_seller_order_list)  && Auth::user()->parent_id == 0) colspan="2" @endif>
                            <div class="font-13 font-weight-bold">
                                @if(!isset($is_seller_order_list))
                                    @if($row->is_course == 1)
                                    <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('course_details',[$row->seller->username,$row->service->seo_url])}}">{{$child->service->title}} </a>
                                    @elseif($child->status == 'new')
                                        <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('order_submit_requirements',$child->order_no)}}">{{$child->service->title}}</a>
                                    @else
                                        <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('buyer_orders_details',$child->order_no)}}">{{$child->service->title}}</a>
                                    @endif
                                @else
                                    <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('seller_orders_details',$child->order_no)}}">{{$child->service->title}}</a>
                                @endif
                            </div>
                            <div>
                                <span class="font-12 text-color-4">
                                    @if(!isset($is_seller_order_list))
                                        @if($row->is_course == 1)
                                            <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('course_details',[$row->seller->username,$row->service->seo_url])}}">
                                        @elseif($child->status == 'new')
                                            <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('order_submit_requirements',$child->order_no)}}">
                                        @else
                                            <a class="text-color-4 view-order-detail-btn" target="_blank" href="{{route('buyer_orders_details',$child->order_no)}}">
                                        @endif
                                        #{{$child->order_no}}</a>
                                    @else
                                        <a class="text-color-6 view-order-detail-btn" target="_blank" href="{{route('seller_orders_details',$child->order_no)}}">#{{$child->order_no}}</a>
                                    @endif
                                &nbsp;|&nbsp; @if($row->is_course == 1) Course @else Service @endif from</span>
                                <a href="{{route('viewuserservices',$child->seller->username)}}">
                                    <span class="text-color-1 font-12 pl-1">{{ ucfirst($child->seller->username) }}</span> 
                                </a>
                            </div>
                        </td>
                        {{-- Order Date --}}
                        <td class="font-13 text-color-6">
                            @if($child->start_date)
                            {{date('M d Y',strtotime($child->start_date))}}
                            @else
                            -
                            @endif
                        </td>
                        {{-- Order Due Date --}}
                        <td class="font-13 text-color-6">
                            @if($child->status != 'new' && $child->status != 'on_hold')
                                @if($child->end_date)
                                    {{date('M d Y',strtotime($child->end_date))}} at {{date('h:i A',strtotime($child->end_date))}}
                                @else
                                -
                                @endif
                            @else
                            -
                            @endif
                        </td>
                        {{-- Delivered Date --}}
                        @if(isset($_GET['status']) && $_GET['status']=='delivered')
                            <td class="text-center">
                                @if($child->delivered_date)
                                {{date('d M Y',strtotime($child->delivered_date))}}
                                @else
                                -
                                @endif
                            </td>
                        @endif
                        {{-- Price --}}
                        @if(!isset($is_seller_order_list) || isset($is_seller_order_list) && Auth::user()->parent_id == 0)
                        <td class="font-13 text-color-6 font-weight-bold">
                            ${{$child->order_total_amount}}
                        </td>
                        @endif
                        {{-- Only for Seller  --}}
                        @if(isset($is_seller_order_list) && $is_seller_order_list == 1)
                        {{-- Affiliate --}}
                        <td class="font-13 text-color-6">
                            @if($child->is_affiliate == 1) Yes @else No @endif
                        </td>
                        <td class="font-13 text-color-6">
                            @if(!empty($child->order_note))
                            <button class="view-invoid-btn open-new-message text-color-4" data-target="#order-note-model-{{$child->id}}" data-toggle="modal"  title="Buyer Note"><i class="fa fa-sticky-note" aria-hidden="true"></i></button>
                            <div id="order-note-model-{{$child->id}}" role="dialog" class="modal fade new-message mfp-hide custommodel" aria-hidden="true">
                                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Order Note</h5>

                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group bold-lable">
                                                <div class="form-group m-form__group row">
                                                    <div class="col-lg-12">
                                                        <?php echo $child->order_note;?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @else
                            <label> - </label>
                            @endif
                        </td>
                        @endif
                        {{-- Status --}}
                        <td class="font-10 text-white">
                            @if($child->status=='active')
                                <span class="bg-primary-blue py-1 px-2 border-radius-8px">IN PROGRESS</span>
                            @elseif($child->status == 'cancelled')
                                <span class="bg-light-gray py-1 px-2 border-radius-8px">{{strtoupper($child->status)}}</span>
                            @elseif($child->status == 'in_revision')
                                <span class="in_revision">IN REVISION</span>
                            @elseif($child->status == 'on_hold')
                                <span class="rejected">ON HOLD</span>
                            @elseif($child->status == 'new')
                                <span class="bg-warning py-1 px-2 border-radius-8px">INCOMPLETE</span>
                            @else
                                <span class="bg-green py-1 px-2 border-radius-8px">{{strtoupper($child->status)}}</span>
                            @endif 
                        </td>
                        {{-- Invoice --}}
                        <td>
                            @if($child->status == 'completed')
                            <form action="{{ route('view_order_invoice')}}" method="GET" target="_blank">
                                @if(!isset($is_seller_order_list) && Auth::user()->parent_id == 0)
                                <input type="hidden" name="hash" value="{{ $child->get_invoice_url($child,1) }}">
                                @else
                                <input type="hidden" name="hash" value="{{ $child->get_invoice_url($child) }}">
                                @endif
                                <button class="view-invoid-btn text-color-4" type="submit" title="View Invoice"><i class="far fa-file-alt"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @endif

                @endforeach
                @if(count($Order)==0)
                <tr>
                    <td colspan="9" class="text-center">
                        No order found
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
        <div class="text-center mt-3 order-pagination text-white">
            @if(isset($Order))
                {{ $Order->appends(['search' => isset($_GET['search'])?$_GET['search']:'', 'status' => isset($_GET['status'])?$_GET['status']:'', ])->links("pagination::bootstrap-4") }}
            @endif
        </div>
    </div>
</div>