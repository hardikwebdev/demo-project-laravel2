<form id="lecture_store_form" name="lecture_store_form" action="{{ route('course.content.store',[$Course->seo_url,$secret]) }}" method="post">
    @csrf
    <input type="hidden" name="current_step" value="5">
    <div class="border-0 px-md-5 row">
        {{-- Name --}}
        <div class="col-md-12 form-group">
            <label class="font-16 text-color-6">Title</label>
            <input name="name" id="store_lecture_name" type="text" class="font-14 form-control summary" placeholder="Enter Title">
            <div class="error fs-14 lecture_name_error"></div>
        </div>

        <!-- Select Media Type -->
        <div class="col-md-12 form-group">
            <div class="form-check form-check-inline">
                <input class="form-check-input select-media-type" type="radio" name="temp_media_type" id="video_media_type" value="video" checked>
                <label class="form-check-label" for="video_media_type">Video</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input select-media-type" type="radio" name="temp_media_type" id="article_media_type" value="article">
                <label class="form-check-label" for="article_media_type">Article</label>
            </div>
            <input type="hidden" name="media_type" id="hid_media_type" value="video" />
        </div>
      
        <div class="col-md-12 upload-file-section">
            <!-- Select Video File -->
            <div class="media-upload-form"> 
                <div class="video-input-button dz-clickable custome-dropzone-media-upload cust-dash-border" id="video-dropzone">
                    <span class="dz-default dz-message text">
                        <img src="{{url('public/frontend/images/upload-cloud.png')}}" alt="">
                        <h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <span class="text-color-1">browse</span></h1> 
                        <div class="form-group">
                            <input type="hidden" name="upload_media" id="upload_media">
                            <div class="media-validation-message error fs-14"></div>
                        </div>
                    </span>
                </div>
            </div>

            <!-- Article -->
            <div class="upload-article hide"> 
                <div class="form-group">
                    {{Form::textarea('upload_article',null,["class"=>"form-control","placeholder"=>"","id"=>"upload_article"])}}
                    <div class="error" id="article_error_msg"></div>
                </div>
                <div class="form-group">
                    <label>Estimate Reading Time</label>
                    {{Form::text('duration','',["class"=>"form-control col-md-3 estimate-times","placeholder"=>"00:00:00"])}}
                    <div class="error duration-error"></div>
                </div>
            </div>

        </div>

        {{-- Uploading Process --}}
        <div class="col-md-12 media-upload-process">
            <!-- Processing -->
            <div class="media-uploading-process table table-striped previews" style="display: none;">
                <div class="template file-row">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex  align-items-center w-100">
                            <span class="preview max-w-5"><img data-dz-thumbnail style="width: 15px !important;" /></span>
                            <label  class="mb-0 font-16 text-color-2 font-weight-bold pl-2 name upload-media-name" data-dz-name></label>
                            <label class="mb-0 font-14 text-color-4 ml-2 size min-w-15" data-dz-size>0</label>
                        </div>    
                        <a data-dz-remove href="Javascript:;">
                            <i class="fas fa-times text-color-4"></i>
                        </a>
                    </div>
                    <div id="total-progress" class="progress mt-2 progress-striped active" style="height:7px;">
                        <div class="progress-bar progress-bar-success" role="progressbar" style="width: 0%;" data-dz-uploadprogress aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-color-1 font-11 font-weight-bold text-right mt-1 mb-0"><span class="progress-percentage">0%</span> done</div>
                    <strong class="error text-danger" data-dz-errormessage></strong>
                    
                    <div id="actions" class="d-none">
                        <button type="button" class="btn btn-primary start_upload">
                            Upload
                        </button>
                        <button type="reset" class="btn btn-warning cancel_upload">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <div class="show-video-processing text-center d-none text-color-1 font-20">
                <i class="fa fa-spin fa-spinner"></i> Your video is processing...
            </div>
            <!-- Complete to show screen -->
            <div class="show-media" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img class="img-fluid cart-logo media_show border border-secondary" />
                        <div class="mx-3">
                            <div class="mb-0 font-16 text-color-2 font-weight-bold media_name"></div>
                            <div class="mb-0 font-14 text-color-4 media_size"></div>
                        </div>  
                    </div>
                    <a href="Javascript:;" class="remove-file">
                        <i class="far fa-trash-alt text-color-2"></i>
                    </a>
                </div>
            </div>
        </div>     
        
        <div class="col-md-12 error" id="file_upload_error_msg">
        </div>
        
        {{-- Video Description --}}
        <div class="col-md-12 video-description">
            <div class="form-group mt-3">
                <label>Description</label>
                <textarea class="form-control" name="video_description" rows="4"></textarea>
            </div>
        </div>

        {{-- Preview Section --}}
        <div class="col-md-4 mt-3">
            <div class="cusswitch enable_show_preview">
                <label class="notification " for="notification">Do you want to allow preview?</label>
                <label class="pm-switch">
                    {{ Form::checkbox('is_preview',1, false ,["class"=>"switch-input","id"=>"is_preview"]) }}
                    <span class="switch-label" data-on="Yes" data-off="No"></span> 
                    <span class="switch-handle"></span>
                </label>
            </div>  
        </div>

        {{-- Submit Form --}}
        <div class="col-md-8 d-flex justify-content-end align-self-end">
            <button type="button" class="btn text-color-1 bg-transparent btn-sm cancel-lecture-form" data-id="{{$secret}}">Cancel</button>
            <button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 submit-lecture-btn  btn-sm ml-2">Submit <i class="fa fa-spin fa-spinner d-none"></i> </button>
        </div>
    </div>
</form>