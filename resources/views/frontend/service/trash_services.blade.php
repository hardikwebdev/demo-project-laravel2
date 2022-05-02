@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Trash Services')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Trash Services</h2>
			</div>    
		</div>    
	</div>    
</section>

<!-- popular service -->
<section class="block-section transactions-table pad-t4">
	<div class="container">
        @include('layouts.frontend.messages')
        <div class="row cus-filter align-items-center">
			<div class="col-12 pad0">
                <div class="offset-2 col-10">
                    <span class="trash_msg">Services that have been in Trash more than 90 days will be automatically deleted.</span>
                    @if(count($Service) > 0)
                    <a href="{{ route('remove_all_service') }}" class="ml-3">Empty Trash now</a>
                    @endif
                </div>
            </div>
        </div>
        @foreach($Service as $row)
		<div class="row service-item">
			<div class="col-md-4">
				<div class="service-box">
					<div class="service-image">
                        @if(isset($row->images[0]))
                            @if($row->images[0]->photo_s3_key != '')
                            <img alt="product-image" class="img-fluid img-max-height"  src="{{$row->images[0]->media_url}}">
                            @else
                            <img alt="product-image" class="img-fluid img-max-height"  src="{{url('public/services/images/'.$row->images[0]->media_url)}}">
                            @endif
                        @endif
					</div>
					<div class="service-detail">
						<div class="service-title">
                            <p class="text-header">
                                {{$row->title}}
                            </p>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-5">
				<div class="package-detail">
					<div class="package-name">
						<p>Package name: 
							<span>{{isset($row->basic_plans->package_name)?$row->basic_plans->package_name:''}}{{$row->package_name}}
							</span>
						</p>  
					</div>
					@if($row->is_recurring == 0)
					<div class="package-delivery">
						<p>Delivery: 
							<span>
								{{isset($row->basic_plans->delivery_days)?$row->basic_plans->delivery_days.' days':''}}
							</span>
						</p>  
					</div>
					@endif

					<div class="package-price">
						<p>Price: 
							<span>
								{{isset($row->basic_plans->price)?'$'.$row->basic_plans->price:''}}
							</span>
						</p>  
					</div> 
					
				</div>
			</div>
			<div class="col-md-3">
				<div class="service-btn">
                    @if(App\User::is_soft_ban() == 0)
					<div class="prompt-btn">
						<button type="button" data-url="{{ route('restore_trashed_service',$row->seo_url) }}" class="btn restore_btn">Restore</button>
                    </div>
                    @endif
                    <div class="prompt-btn margin_left"> 
						<button type="button" data-url="{{ route('remove_service',$row->seo_url) }}" class="btn trash_btn">Delete</button>
					</div>
				</div>
			</div>
		</div>
        @endforeach
        
        <div class="clearfix"></div>
		{{ $Service->links("pagination::bootstrap-4") }}
    </div>
</section>
@endsection

@section('css')
<style>
.trash_btn {
	background: linear-gradient(90deg, #ef3d4e , #ff8e98 ) !important;
}
.margin_left {
    margin-left: 10px;
}
.service-btn .prompt-btn {
    float: initial;
}
.trash_msg {
    color: gray;
    /* padding-left: 100px; */
}
</style>
@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script>
$('.restore_btn').on('click',function(){
    var url = $(this).data('url');
    bootbox.confirm({
        message: 'Are you sure you want to restore this service?',
        buttons: {
            confirm: {
                label: 'Continue',
                className: 'btn-default'
            },
            cancel: {
                label: 'Cancel',
                className: 'btn-default'
            }
        },
        callback: function (result) {
            if(result == true){
                window.location.href = url;
            }
        }
    });
});

$('.trash_btn').on('click',function(){
    var url = $(this).data('url');
    bootbox.confirm({
        message: 'Are you sure you want to permanently delete this service?',
        buttons: {
            confirm: {
                label: 'Continue',
                className: 'btn-default'
            },
            cancel: {
                label: 'Cancel',
                className: 'btn-default'
            }
        },
        callback: function (result) {
            if(result == true){
                window.location.href = url;
            }
        }
    });
});
</script>
@endsection