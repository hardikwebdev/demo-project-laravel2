@extends('layouts.frontend.main')
@section('pageTitle','demo - Order Success')
@section('content')


<!-- Masthead -->
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4 d-flex justify-content-center">
        <div class="d-flex align-items-center flex-column flex-md-row justify-content-md-around parent-cart-step">
            <p class="text-color-1 mb-0 font-16"><i class="fas fa-check cart-step-1 mx-2 font-10 py-2"></i> Order Details </p>
            <i class="fas fa-chevron-right arrow-right"></i>
            <p class="text-color-1 mb-0 font-16"><i class="fas fa-check cart-step-1 mx-2 font-10 py-2"></i> Payment Options </p>
            <i class="fas fa-chevron-right arrow-right"></i>
            <p class="text-color-1 mb-0 font-16"><span class="font-13 cart-step-1  font-weight-bold mx-2">3</span>Submit Requirements </p>
        </div>
	</div>
    </div>
</header>

<section class="transactions-table py-5">
    <div class="container mt-4 mt-md-0 font-lato">
        <div class="alert-success mx-auto py-3">
            <div class="d-flex justify-content-center align-items-center">
                <div>
                    <img src="{{url('public/frontend/images/Tik-mark.png')}}" class="img-fluid px-3" alt="">
                </div>
                <div>
                    <p class="mb-0 font-20 text-color-8 font-weight-bold">  Thank You for Your Purchase!</p>
                    {{-- <span class="font-16 font-weight-normal text-color-8">Submit requirements to start orders.</span> --}}
                </div>
            </div>
        </div>
    </div>

	@if(count($Order))
    <div class="container font-lato">   
        <div class="row mt-5">
            <div class="col-12 col-md-8 col-lg-9 pr-md-0 pr-xl-3">  
                <div class="row pt-3 pb-2">
                    <div class="col-12 col-lg-6">
                        <h1 class="font-24 text-color-2 font-weight-bold">Summary</h1>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="py-1 text-right">
                            <span class="font-14 text-color-4 pr-3 border-gray border-right">
                                <i class="far fa-clock" aria-hidden="true"></i>
                                <span class="mx-2"> {{$Order->total_days}} sponsor days </span>
                            </span> 
                            <a href="{{route('sponsered_transaction')}}" class="ml-4">
                                View Sponsored Services
                            </a>
                            {{-- <span class="font-14 text-color-4 ml-3">
                                <i class="far fa-user" aria-hidden="true"></i>
                                <span class="mx-2">
                                    <a href="{{route('viewuserservices',$row->seller->username)}}">{{$row->seller->Name}}</a>
                                </span>
                            </span> --}}
                        </div>
                    </div>
                </div>
                <div class="row summary mx-0">
                    <div class="col-12">
                        <div class="py-3">
                            <p class="font-18 font-weight-bold text-color-1 mb-0">
                                <a href="{{route('services_details',[$Order->Service->user->username,$Order->Service->seo_url])}}" class="text-capitalize"> {{$Order->Service->title}}
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="col-12 text-center">
                        <table class="table table-borderless table-responsive-sm">
                            <thead>
                                <tr>
                                    <th class="font-12 text-color-4 font-weight-normal text-left">{{$Order->boosting_plan->name}}</th>
                                    <th class="font-12 text-color-4 font-weight-normal">Date</th>
                                    <th class="font-12 text-color-4 font-weight-normal">Selected plan</th>
                                    <th class="font-12 text-color-4 font-weight-normal">Coupon discount</th>
                                    <th class="font-12 text-color-4 font-weight-normal">Total</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-gray">
                                <tr>
                                    <td class="font-14 text-color-6  text-left">
                                        {!! nl2br($Order->boosting_plan->description) !!}
                                    </td>
                                    <td class="font-14 text-color-6">
                                        @if($Order->plan_id == 4 || $Order->plan_id == 5)
                                            @php
                                            $dates_array = [];
                                            foreach($Order->boosting_assign_dates as $row1){
                                                $dates_array[] = $row1->date;
                                            }
                                            @endphp
                                            {!! array_to_date_list($dates_array,'<br>') !!}
                                        @else
                                            {!! date_range_to_list($Order->start_date,$Order->end_date,'<br>') !!}
                                        @endif
                                    </td>
                                    <td class="font-14 text-color-6">
                                        {{($Order->slot == 2)?$Order->boosting_plan->sub_price:$Order->boosting_plan->price}}
                                    </td>
                                    <td class="font-14 text-color-6">
                                        @if($Order->coupon_id)
                                            @if($Order->coupon_amount_type == 1)
                                                @php
                                                if($Order->slot == 2){
                                                    $subtotal = $Order->boosting_plan->sub_price * $Order->total_days;
                                                }else{
                                                    $subtotal = $Order->boosting_plan->price * $Order->total_days;
                                                }
                                                @endphp
                                                {{ ($subtotal * $Order->coupon_amount) / 100 }}
                                            @else
                                                {{ $Order->coupon_amount }}
                                            @endif
                                        @else 
                                            -
                                        @endif
                                    </td>
                                    <td class="font-14 text-color-6">${{$Order->amount}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                        
            <div class="col-12 col-md-4 col-lg-3">
                <div class="row mt-4 mt-md-0">
                    <div class="col-12">
                        <div class="p-3 summary">
                            <p class="text-left text-color-2 py-2 font-18 font-weight-bold mb-0">Transaction Information</p>
                            <div class="py-2">
                                <p class="mb-0 font-12 text-color-3">Transaction ID:</span> 
                                <p class="mb-0 font-14 font-weight-bold text-color-2">{{$Order->txn_id}}</span>
                            </div>
                            <div class="py-2">
                                <p class="mb-0 font-12 text-color-3">Transaction Date:</span> 
                                <p class="mb-0 font-14 font-weight-bold text-color-2">{{date('M d,Y',strtotime($Order->created_at))}}</span>
                            </div>
                            <div class="py-2">
                                <p class="mb-0 font-12 text-color-3">Total Amount:</span> 
                                <p class="mb-0 font-14 font-weight-bold text-color-2">${{$Order->amount}}</span>
                            </div>    
                        </div>
                    </div>
                </div>
            </div>       
        </div>
    </div>
    @endif
</section>

@endsection

@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>
@endsection
@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>
@endsection

@section('dataLayerTransction')
@if(count($Order) > 0 )
@foreach($Order as $row)
<script>
	window.dataLayer = window.dataLayer || [];
	dataLayer.push({
		'transactionId': '{{$row->txn_id}}',
		'transactionAffiliation': "{{($row->affiliate)?$row->affiliate->username:''}}",
		'transactionTotal': {{number_format($row->price * $row->qty,2,'.','')}}, 
		'transactionProducts': [{
			'sku': '{{$row->service->seo_url}}',
			'name': '{{$row->service->title}}',
			'category': '{{($row->service->category)?$row->service->category->category_name:''}}',
			'price': '{{number_format($row->price,2,'.','')}}',  
			'quantity': '{{$row->qty}}' 
		}]
	});
</script>
@endforeach
@endif
@endsection