@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Messages')
<style type="text/css">
	.add_reply{
		border-radius: 0px 5px 5px 0px;
    border-color: transparent;
    font-size: 13px;
    padding: 8.5px;
    background: linear-gradient(90deg, #35abe9 , #08d6c1 );
    border: none;
	}
</style>
@section('content')

<section class="profile-header filter-header message-converstion">
	<div class="container">
		<div class="profile-detail">
			<div class="row">
				<div class="container cus-filter">
					<h2 class="heading mb-3">Manage Template</h2>	
					<!-- <a href="{{ url('show_canned_message') }}" class="btn btn-info mb-3" style="float: right;">Add Canned Reply</a> -->
					<button type="button" class="btn btn-primary add_reply_only" style="float: right;">Add Canned Reply</button>
				</div>    
			</div>    
		</div>    
	</div>
</section>
<!-- Conversations -->

<section class="block-section">
	<div class="container">
		<div class="messagebox custom-margin-top">
			<div class="cus-container-two">    

				<div class="messaging chat-screen table-responsive">
				
					<table class="manage-sale-tabel">
						<thead>
							<tr class="manage-sale-head">
								<td class="text-center">User</td>
								<td class="text-center">Type</td>
								<td width="20%" class="text-center">Title</td>
								<td class="text-center">Template</td>
								<td class="text-center">Action</td>
							</tr>
						</thead>
						<tbody>
							@foreach($template as $row)
							<tr id="{{$row->id}}">
								<td class="text-center">
									<div class="text-align-webkit-center">
										<a href="{{route('viewuserservices',$row->users->username)}}" style="display: inline-block;margin-right: 5px;">
											<figure class="user-avatar small">	
												<img src="{{get_user_profile_image_url(Auth::user())}}" alt="profile-image">
											</figure>
										</a>
										<a href="{{route('viewuserservices',$row->users->username)}}" style="display: inline-block;">
											{{$row->users->username}}
										</a>
										@if(App\User::checkPremiumUser($row->users->id) == true)

											<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="10"></img>
										@else
											<i class="fa fa-check" aria-hidden="true"></i>
										@endif
									</div>
								</div>
							</div>
								</td>
								<td class="text-center custom-word-break">
									@if($row->template_for == 1)
										<span>Delivered</span>
									@else
										<span>Chat</span>
									@endif
								</td>
								<td class="text-center custom-word-break">
									{{$row->title}}
								</td>

								<td class="text-center custom-word-break">
									 {!!$row->message!!} 
								</td>
								<td class="text-center">
								   <button style="margin-bottom: 3px" type="button" data-id="{{$row->id}}" class="btn btn-primary btn-sm edit_template add_reply" data-type="{{ @$row->template_for}}">Edit</button>
								   <button  type="button" class="btn btn-danger btn-sm delete_template_custom" data-id="{{$row->id}}">Delete</button>
								</td>
							</tr>
							@endforeach
							@if(count($template)==0)
							<tr>
								<td colspan="7" class="text-center">
									No Template Found
								</td>
							</tr>
							@endif
						</tbody>
					</table>
					<div class="clearfix"></div>

					<!-- PAGER -->
					<div class="text-center">{{ $template->links("pagination::bootstrap-4") }}</div>
				</div>
			</div>
		</div>
	</div>        
</section> 
@include('frontend.seller.save_template_custome') 
@endsection

@section('css')
<style type="text/css">
	#order-table img{
		vertical-align: middle;
	}

	@media screen and (max-width: 735px){
		.custom-table{
			display: block !important;
		}
	}
</style>
@endsection

@section('scripts')

<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">

	$('#tem_descriptions').each(function(){
		
	});

	/* Replace With CKEditor */
	if($('#tem_descriptions').length > 0){
		ClassicEditor.create( document.querySelector( '#tem_descriptions' ) )
		.then( newEditor => {
			temp_description = newEditor;
			temp_description.model.document.on( 'change:data', ( evt, data ) => {
				var descriptions =  temp_description.getData();
				if(descriptions==''){
					$('.show_error_update').html("<label style='color:red;margin-left:12px'>Content should not be blank</label>");
				}else{
					$('.show_error_update').html("");
				}
			});
		})
		.catch( error => {
			console.error( error );
		});
	}
	/* END Replace With CKEditor */
	$('#tem_chat_message').keyup(function () { 
		if($.trim($(this).val()) == ''){
			$('.show_error_update').html("<label style='color:red;margin-left:12px'>Content should not be blank</label>");
		}else{
			$('.show_error_update').html("");
		}
	});

	$(document).on('click','.add_reply_only',function(){
		$('#tempalte_pop_add').modal('show');
		$('.ckeditor_data').hide();
		$('.imoji_data').show();
		$('#tem_chat_message').html("");
	})

	$(document).on('click','.submit_template',function(){
		var dataVal=$('#edit_title').val();
		var type = $(this).data('type');
		var itemVal=$('#tem_chat_message').val();
		var is_valid = true;
		
		if($.trim(dataVal) == ''){
			$('.show_error_update_title').html("<label style='color:red;margin-left:12px'>Title should not be blank</label>");
			is_valid = false;
		}

		if(type == 1){
			if(temp_description.getData() == ''){
				$('.show_error_update').html("<label style='color:red;margin-left:12px'>Content should not be blank</label>");
				is_valid = false;
			}	
		}else{
			if($.trim(itemVal) == ''){
				$('.show_error_update').html("<label style='color:red;margin-left:12px'>Content should not be blank</label>");
				is_valid = false;
			}
		}

		return is_valid;
		
	})

	$(document).on('click','#submit_new_canned',function(){

		var data=$('#update_template_add').serialize();

		$.ajax({
           type: "POST",
           url: "{{ url('seller/update_template') }}",
           data: data, // serializes the form's elements.
           success: function(data)
           {
           	if(data.status == 3)
           	{
           		 $('.show_error').html("<label style='color:red;margin-left:12px'>"+data.message+"</label>");
           	}
           	else if(data.status==2)
           	{
           		$('.show_error').html("<label style='color:red;margin-left:12px'>"+data.message+"</label>");
           	}
           	else if(data.status==4)
           	{
           		$('.show_error').html("<label style='color:red;margin-left:12px'>"+data.message+"</label>");
           	}
           	else
           	{
           		 $('#tempalte_pop_add').modal('hide');
           		 alert_success('Template Added Successfully');
           		 setTimeout(function(){
           		 	location.reload();
           		 },3000)
               		
           	}
             
           }
         });
	});

	$('#tempalte_pop').on('hidden.bs.modal', function () {
		$('.show_error_update_title').html('');
		$('.show_error_update').html('');
	});
	
</script>
@endsection