@extends('layouts.frontend.main')
@section('pageTitle', 'Job Proposals')
@section('content')

<section class="profile-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row">
				<div class="container cus-filter">
					<h2 class="heading mb-3">Job Proposals</h2>
				<div class="col-md-12 pad0">
					<div class="custom-order-request-heading"><span>{{count($jobs)}}</span> Proposals Found</div>
				</div>
				</div>    
			</div>    
		</div>    
	</div>
</section>

<section class="custom-order-section sponsored-section block-section">
	<div class="container">
		<div class="cus-filter-data">
			<div class="cus-container-two">    
				<div class="table-responsive">
					<table class="manage-sale-tabel custom">
						<thead>
							<tr class="manage-sale-head custom-bold-header">
								<td class="text-center">Service Title</td>
								<td class="text-center">Delivery Days</td>
								<td class="text-center">Offer Price</td>
								<td class="text-center">Bid Date</td>
								<td class="text-center">Status</td>
							</tr>
						</thead>
						<tbody>
							@foreach($jobs as $key => $bids)
							<tr>
								<td class="text-center">
									<a class="text-capitalize" href="{{ route('show.job_detail',$bids->service->seo_url)}}">
										{{ isset($bids->service->title) ? $bids->service->title : ''}}
									</a>
								</td>
								<td class="text-center">
									{{ isset($bids->delivery_days) ? $bids->delivery_days : ''}}
								</td>
								<td class="text-center">
									{{ isset($bids->price) ?'$'.$bids->price : ''}}
								</td>
								<td class="text-center">
									{{date('d M Y',strtotime($bids->created_at))}}
								</td>
								<td class="text-center">
									@if($bids->status == 'pending')

										<button type="submit" data-id="{{$bids->secret}}" class="custom-sucess-btn acceptProposal">Accept</button>

										<button type="button" class="custom-danger-btn open-decline-order rejectProposal" data-id="{{$bids->secret}}">Decline</button>

									@else
										@if($bids->status=='new')
										<span class="pending">Waiting for response from {{$bids->user->username}}</span>
										@elseif($bids->status=='accepted')
										<span class="completed">Job Proposal Accepted</span>
										@endif
									@endif

								</td>
							</tr>
							@endforeach
							@if(count($jobs)==0)
							<tr>
								<td colspan="7" class="text-center">
									No request found
								</td>
							</tr>
							@endif
						</tbody>
					</table>

					<div class="clearfix"></div>
					<!-- PAGER -->
					<div class="text-center">{{ $jobs->appends([])->links("pagination::bootstrap-4") }}</div>
				</div>
			</div>
		</div>
	</div>        
</section>  


@endsection


@section('scripts')
<!--Bootbox-->
<script src="{{front_asset('js/bootbox.min.js')}}"></script>

<script type="text/javascript">
	
    $(document).on('click','.rejectProposal',function(){
             var id=$(this).data('id');
        return bootbox.confirm('Are you sure to reject this Job proposal?', function (result) {
            if (result) {
                
                $.ajax({
                    url: '{{route("reject.proposal_seller")}}',
                    type: 'get',
                    data: "id="+id,
                    success: function (data) {
                        if(data.success == true)
                        {
                            location.reload();
                        }
                    },
                    error: function (xhr, desc, err) {
                        //console.log(xhr);
                        //console.log("Details: " + desc + "\nError:" + err);
                    }
                });
            }
        });
    });


    $(document).on('click','.acceptProposal',function(){
             var id=$(this).data('id');
        return bootbox.confirm('Are you sure to accept this Job proposal?', function (result) {
            if (result) {
                
                $.ajax({
                    url: '{{route("accept.proposal_seller")}}',
                    type: 'get',
                    data: "id="+id,
                    success: function (data) {
                        if(data.success == true)
                        {
                            location.reload();
                        }
                    },
                    error: function (xhr, desc, err) {
                        //console.log(xhr);
                        //console.log("Details: " + desc + "\nError:" + err);
                    }
                });
            }
        });
    });

</script>

@endsection