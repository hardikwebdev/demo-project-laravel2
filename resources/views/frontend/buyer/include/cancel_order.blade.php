<div class="modal fade" id="cancelorderpopup" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<h4 class="modal-title">Need to change or cancel your order?</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 new-seller-review">
				{{ Form::open(['route' => ['cancel_order',$Order->id], 'method' => 'POST','class'=>'','id'=>'frmCancelOrder']) }}
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group">
							<label>Enter Cancel Reason</label>
							<textarea class="form-control" rows="6" id="cancel_note" name="cancel_note" placeholder="" maxlength="2500"></textarea>
							<div class="text-danger text-left note-error"></div>
							<p class="text-right"><span id="cancel_note_chars">0</span>/2500 character Max</p>  
						</div>
					</div>
				</div>
				<div class="modal-footer border-0 px-3 px-md-5 pt-5 justify-content-around">
					<button type="button" class="btn text-color-1 bg-transparent" data-dismiss="modal">Cancel</button>
					<button type="submit" id="cancel_hidden" style="display: none;"> </button>
					<button type="button" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5" id="cancel_order">Cancel Order</button>
				</div>
				{{Form::close()}}
			</div>
		</div>
	</div>
</div>
