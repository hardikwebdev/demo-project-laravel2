<form id="portfolio_form" name="portfolio_form" action="{{ route('portfolio.create') }}" method="post">
	@csrf
	<div class="modal-header modal-header-border-none border-0">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<div class="modal-body pt-0 border-0 px-3 px-md-5">
		<h3 class="font-weight-bold font-20 text-color-6 text-center">Create New Project</h3>
		<div class="form-group title">
			<p class="mb-1 font-16 text-color-6 mt-5 font-weight-bold">Project Name</p>
			<input name="title" id="title" type="text" class="font-14 form-control summary" placeholder="Enter your project name">
			<div class="error fs-14"></div>
		</div>
		<div class="form-group description">
			<p class="mb-1 font-16 text-color-6 mt-3 font-weight-bold">Description</p>
			<textarea name="description" class="summary font-14 w-100 p-2 bg-transparent" cols="30" rows="4" id="description" placeholder="Tell us more about the project"></textarea>
			<div class="error fs-14"></div>
		</div>
		<div class="delivery-now mt-4">
			<p class="mb-1 font-16 text-color-6 mt-3 font-weight-bold">Attach file</p>
			<!-- Select File -->
			<div class="media-upload-form"> 
				<div class="dropzone fileinput-button dz-clickable custome-dropzone-media-upload">
					<span class="dz-default dz-message text">
						<img src="{{url('public/frontend/images/upload-cloud.png')}}" alt="">
						<h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <span class="text-color-1">browse</span></h1> 
						<h3 class="font-14 text-color-4 font-weight-normal">Maximum file size 20MB(Image) or 250MB(Video) </h3>
						<div class="form-group">
							<input type="hidden" name="upload_media" id="upload_media">
							<div class="media-validation-message error fs-14"></div>
						</div>
					</span>
				</div>
				<p class="mb-1 font-12 text-color-4 mt-1">Accepted file types: JPG, PNG, WEBM, MP4, MOV</p>   
			</div>

			<!-- Processing -->
			<div class="uploading-process table table-striped previews">
				<div class="template file-row">
					<div class="d-flex justify-content-between align-items-center">
						<div class="d-flex  align-items-center cust-w-95">
							<span class="preview max-w-5"><img data-dz-thumbnail style="width: 15px !important;" /></span>
							<p  class="mb-0 font-16 text-color-2 font-weight-bold pl-2 name upload-media-name" data-dz-name></p>
							<p class="mb-0 font-14 text-color-4 size max-w-15" data-dz-size>0</p>
						</div>    
						<a data-dz-remove href="Javascript:;">
							<i class="fas fa-times text-color-4"></i>
						</a>
					</div>
					<div id="total-progress" class="progress mt-2 progress-striped active" style="height:7px;">
						<div class="progress-bar progress-bar-success" role="progressbar" style="width: 0%;" data-dz-uploadprogress aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<p class="text-color-1 font-11 font-weight-bold text-right mt-1 mb-0"><span class="progress-percentage">0%</span> done</p>
					<strong class="error text-danger" data-dz-errormessage></strong>
					
					<div id="actions" class="d-none">
						<button type="button" class="btn btn-primary start_upload">
							<i class="glyphicon glyphicon-upload"></i>
							<span></span>
						</button>
						<button type="reset" class="btn btn-warning cancel_upload">
							<i class="glyphicon glyphicon-ban-circle"></i>
							<span></span>
						</button>
					</div>
				</div>
			</div>

			<!-- Complete to show screen -->
			<div class="show-media" style="display: none;">
				<div class="d-flex justify-content-between align-items-center">
					<div class="d-flex align-items-center">
						<img class="img-fluid cart-logo media_show" />
						<div class="mx-3">
							<p class="mb-0 font-16 text-color-2 font-weight-bold media_name"></p>
							<p class="mb-0 font-14 text-color-4 media_size"></p>
						</div>  
					</div>
					<a href="Javascript:;" class="remove-file">
						<i class="far fa-trash-alt text-color-2"></i>
					</a>
				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer border-0 px-3 px-md-5 py-4 justify-content-around pb-5">
		<button type="button" class="btn text-color-1 bg-transparent" data-dismiss="modal">Cancel</button>
		<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 submit-btn">Submit</button>
	</div>
</form>