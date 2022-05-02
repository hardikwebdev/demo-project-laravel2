{{ Form::open(['route' => ['dispute_order',$order_id], 'method' => 'POST','id'=>'submit_dispute']) }}
<input type="hidden" name="user_type" value="{{ $user_type}}" >
<h3 class="font-weight-bold font-20 text-color-6 text-center ">File a Dispute</h3>
<p class="font-16 text-color-2 mt-4 font-weight-bold mb-1">Select a reason</p>
<div class="form-group">
<select name="dispute_option" id="dispute_option" class="form-control summary select_reason_next" required>
	<option value="">--Select--</option>
	@foreach($reasons as $reasondata )
		<option value="{{$reasondata['id']}}" class="text-color-2 font-14">{{$reasondata['reason']}}</option>
	@endforeach
</select>
</div>
<div class="dispute_comment">
	<div class="input-container form-group">
		<br><label class="rl-label required">Please describe in as much detail as possible the reason for your dispute, and be sure to include any important information we may need to know about your order.</label>
		<textarea id="dispute_comment" name="dispute_comment" placeholder="Dispute reason..." class="form-control" rows="6" minlength="100" maxlength="2500"></textarea>
	</div>
</div>
<div class="modal-footer border-0 px-3 px-md-5 py-4 justify-content-around">
	<button type="button" class="btn text-color-1 bg-transparent" data-dismiss="modal">Cancel</button>
	<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 select_reason_next">Submit</button> 
</div>
{{Form::close()}}

<script src="{{front_asset('js/radio-link.js')}}"></script>
<script type="text/javascript">

	$(document).ready(function() {
		$('#submit_dispute').bootstrapValidator({
			fields: {
				dispute_option: {
					validators: {
						notEmpty: {
							message: 'Reason required.'
						}
					}
				},
				dispute_comment: {
					validators: {
						notEmpty: {
							message: 'Comment required.'
						}
					}
				}
			}
		});
	});
</script>