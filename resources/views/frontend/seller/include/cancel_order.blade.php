<div id="cancelorderpopup" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-0 border-0 px-3 px-md-5">
                <h3 class="font-weight-bold font-20 text-color-6 text-center px-md-5 mx-md-2">Need to change or cancel your order?</h3>
				{{ Form::open(['route' => ['seller_cancel_order',$Order->id], 'method' => 'POST','class'=>'frmCancelOrder','id'=>'frmCancelOrder']) }}
					<div class="form-group">
						<label>Enter Cancel Reason</label>
						{{-- <textarea class="form-control" rows="6" id="cancel_note" name="cancel_note" placeholder="" maxlength="2500"></textarea> --}}
						<textarea class="summary text-color-2 font-14 w-100 p-2 bg-transparent" maxlength="2500" rows="6" id="cancel_note" name="cancel_note" placeholder=""></textarea>
						<div class="text-danger text-left note-error"></div>
						<p class="pull-right"><span id="cancel_note_chars">0</span>/2500 character Max</p>  
					</div>
					<div class="clearfix"></div>
					<div class="modal-footer border-0 justify-content-center mb-2">
						<button type="button" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5" id="cancel_order">Cancel Order</button>
						<button type="submit" id="cancel_hidden" style="display: none;"></button>
					</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>