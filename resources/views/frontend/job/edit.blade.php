@extends('layouts.frontend.main')
@section('pageTitle','demo - Edit Job')
@section('content')
@section('topcss')
<link href="{{url('public/frontend/assets/css/dropzone.css')}}" rel="stylesheet">
<style type="text/css">
    
.jobImg img
{
    height: 100px !important;
    width: 180px !important;
}
.jobImg
{
    margin-right: 120px;
    margin-top: 10px;
}
.jobImg video
{
    height: 100px !important;
    width: 180px !important;
}

</style>
@endsection
<!-- Display Error Message -->
@include('layouts.frontend.messages')

<!-- header Title -->
<section class="sub-header product-detail-header m-purple-bg ">
    <div class="container">
        <div class="m-card-img m-card-img-bottom  pb-4">
            <img src="{{url('public/frontend/images/user-02.jpg')}}" alt="" class="img-fluid m-img-roundbr img-round d-inline">
            <div class="m-card-contet ml-4">
                <h5 class="text-white ">Edit Job</h5>
                <!-- <p class="text-white mb-0">Settings <i class="fas fa-cog ml-1"></i></p> -->
            </div>
        </div> 
    </div>
</section>
<!-- End header Title -->
<!-- header Title -->
<section class="sub-header product-detail-header">
    <div class="container">
        <div class="row align-items-center pb-4">
            <div class="col-lg-7">
                <h4 class="  m-color-gray ">Edit Job</h4>
            </div>
            <div class="col-lg-5 m-text-right">
                <!--used to save job as active or draft-->
                <button  type="button" id="draft" class="btn d-inline mbtn-blue m-btn-blue m-brd-rad-15 pr-4 pl-3"><i class="fas fa-save mr-2"></i> Save Draft</button>
                <button  type="button" id="publish" class="btn d-inline m-btn-green m-wdth-200 m-brd-rad-15">Publish Job<i class="fas fa-chevron-right pull-right mt-1"></i></button>

            </div>
        </div>    
    </div>
</section>
<!-- End header Title -->
<!-- content with Sidebar -->

<section class="product-block pt-2 mt-5">
    <div class="container">



        <div class="row ">

            
            <div class="col-lg-7">
                 {{ Form::open(['route' => ['jobs.update'], 'method' => 'POST', 'id' => 'frmServiceOverview1']) }}
                 <input type="hidden" name="_token" value="{{ Session::token() }}">
                 <input type="hidden" name="service_id" value="{{ isset($service->secret) ? $service->secret : ''}}">
                <div class="">
                    <div class="row">
                        @if($service->revisions != null)
                            <div class="col-lg-12">
                                @if ($service->is_revision_approved == 0)
                                    <h5 class="font-bold">Note:<span class="text-info">Your changes are pending for admin approval.</span>
                                    </h5>
                                @elseif($service->is_revision_approved == 2)
                                    <h5>
                                        <b>Note:<span class="text-info"> Your changes are <span class="text-danger">rejected</span> by admin
                                        </span></b>
                                        <div class="font-16">
                                            @if($service->revisions->reject_reason != "") Reason: <span class="font-14 text-danger">{{$service->revisions->reject_reason}}</span> @endif
                                        </div>
                                    </h5>
                                @endif
                            </div>
                        @endif
                        <div class="col-lg-12">
                            <div class="form-group">
                                  <!--Input job title-->
                                <label for="fullname">Job Title</label>
                                <input type="text" id="job_title" name="job_title" class="form-control" value="{{ isset($service->title) ? $service->title : ''}}" required="true" placeholder="Enter your Job Title here..."  autocomplete="off" data-bv-field="job_title">
                                 <span class="getErrorTitle"></span>
                           </div>
                           <div class="imgSet"></div>
                           <input type="hidden" name="status" id="status">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <!--Enter category-->
                                <label>Category</label>
                                @if($service->category->restrict_for_bluesnap == 1 || $service->subcategory->restrict_for_bluesnap == 1)
                                {{ Form::select('dis_category_id', [''=>'Select Category']+$categories,$service->category_id,['class' => 'form-control','disabled']) }}

                                {{ Form::hidden('category_id', $service->category_id,['data-bv-field'=>'category_id','id'=>'category_id','class' => 'form-control']) }}

                                @else
                                {{ Form::select('category_id', [''=>'Select Category']+$categories,$service->category_id,['class' => 'form-control','id'=>'category_id', 'data-bv-field' => 'category_id']) }}
                                @endif
                              
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                        <!--Enter sub category-->
                                <label>Sub Category</label>
                                @if($service->subcategory->restrict_for_bluesnap == 1)
                                {{ Form::select('dis_subcategory_id', [''=>'Select Sub Category']+$selected_sub,$service->subcategory_id,['class' => 'form-control','disabled']) }}

                                {{ Form::hidden('subcategory_id', $service->subcategory_id,['data-bv-field'=>'subcategory_id','id'=>'subcategory_id','class' => 'form-control']) }}


                                @else
                                {{ Form::select('subcategory_id', [''=>'Select Sub Category']+$selected_sub,$service->subcategory_id,['class' => 'form-control','id'=>'subcategory_id', 'data-bv-field' => 'subcategory_id']) }}
                                @endif
                            </div>
                        </div>
                        @php
                        $is_display_message = false;
                        if($service->category->restrict_for_bluesnap == 1 || $service->subcategory->restrict_for_bluesnap == 1){
                            $is_display_message = true;
                        }
                        @endphp

                        @if($is_display_message == true)
                        <div class="col-lg-12">
                            <span class="text-danger">You can't update category and sub category, contact administrator to update category.</span>
                        </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group m-taginput-bg">
                                  <!--Enter tags for job-->
                                <label>Add Tags</label>
                                <input type="text" required="true" id="tags" name="tags" class="form-control" autocomplete="off" value="{{ isset($service->tags) ? $service->tags : ''}}" placeholder="">
                                <small class="dangerColor"><i>Maximum 3 tags allowed</i></small><br>
                                <span class="getErrorTag"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group m-green-input">
                                 <!--Enter minimum price-->
                                <label class="mb-2 m-text-green">Min price</label>

                                  <input type="text" id="min_price" name="min_price" class="form-control" required="true" value="{{isset($service->job_min_price) ? $service->job_min_price : 0}}" placeholder="Enter minimum price of job.." autocomplete="off" data-bv-field="min_price">
                                  <span class="getErrorPriceMin"></span>
                               {{-- <select class="price-by form-control" name="min_price" required="true" id="price_by">
                                    <option value="100" data-usd="USD" {{isset($service->job_min_price) && ($service->job_min_price == 100) ? 'selected' : '' }}>100 <span class="pull-right">USD</span></option>
                                    <option value="200" data-usd="USD" {{isset($service->job_min_price) && ($service->job_min_price == 200) ? 'selected' : '' }} >200 <span class="pull-right">USD</span></option>
                                    <option value="300" data-usd="USD" {{isset($service->job_min_price) && ($service->job_min_price == 300) ? 'selected' : '' }} >300 <span class="pull-right">USD</span></option>
                                    <option value="400" data-usd="USD" {{isset($service->job_min_price) && ($service->job_min_price == 400) ? 'selected' : '' }}>400 <span class="pull-right">USD</span></option>
                                </select>--}}
                            </div>
                        </div>
                        <div class="col-lg-6">
                             <!--Enter maximum price-->
                            <div class="form-group m-green-input">
                                <label class="mb-2 m-text-green">Max price</label>
                                   <input type="text" id="max_price" name="max_price" class="form-control"  required="true" autocomplete="off" value="{{isset($service->job_max_price) ? $service->job_max_price : 0}}" placeholder="Enter maximum price of job.." data-bv-field="max_price">
                                   <span class="getErrorPrice"></span>
                                {{--<select class="price-by form-control" required="true" name="max_price" id="price_high">
                                    <option value="100" data-usd="USD" {{isset($service->job_max_price) && ($service->job_max_price == 100) ? 'selected' : '' }}>100 <span class="pull-right">USD</span></option>
                                    <option value="200" data-usd="USD" {{isset($service->job_max_price) && ($service->job_max_price == 200) ? 'selected' : '' }}>200 <span class="pull-right">USD</span></option>
                                    <option value="300" data-usd="USD" {{isset($service->job_max_price) && ($service->job_max_price == 300) ? 'selected' : '' }}>300 <span class="pull-right">USD</span></option>
                                    <option value="400" data-usd="USD" {{isset($service->job_max_price) && ($service->job_max_price == 400) ? 'selected' : '' }}>400 <span class="pull-right">USD</span></option>
                                </select>--}}
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <!--Enter Description-->
                                <label class="mb-2 ">About This Project</label> 
                                <div class="ckeditor_data">
						            {!! Form::textarea('description',str_replace( '&', '&amp;',$service->descriptions),["class"=>"form-control1","id"=>"textarea_ck",'rows'=>4]) !!}
                                    <span class="getErrorDetail"></span>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                       
                </div>
                   {{ Form::close() }}
            </div>
        
            <div class="col-lg-5">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                                 <!--Enter  media for job-->
                            <label>File Uploads</label>


                            <form action="{{route('store.job_images')}}" method="post" id="imagedropzone" class="dropzone dropzone-file-area" files='true'>
                                <input type="hidden" name="_token" value="{{ Session::token() }}">
                            </form>
                              <span class="getErrorUpload"></span>
                        </div>
                    </div>
                </div>
                <div class="row ml-2">
                    <!--Display all media at the time of creating or editing-->
                    @if(count($service_imgs) > 0)
                    <div class=" row">
                        @foreach($service_imgs as $data)
                            @if($data->media_type == 'image')
                                <div class=" col-md-2 jobImg imgdiv_{{$data->secret}}">
                                    @if($data->photo_s3_key != '')
                                         <a href="{{route('remove_media_job',$data->secret)}}"><span class="custom-danger-btn remove-image-pin">X</span></a>
                                    @endif

                                    @if($data->photo_s3_key != '')

                                    <img src="{{$data->media_url}}" class="fullimage fullimage-custom">
                                    @else
                                    <img src="{{url('public/services/images/'.$data->media_url)}}" class="fullimage fullimage-custom">
                                    @endif
                                </div>
                            @elseif($data->media_type == 'video')
                                <div class=" col-md-2 jobImg imgdiv_{{$data->secret}}">
                                    @if($data->photo_s3_key != '')
                                         <a href="{{route('remove_media_job',$data->secret)}}"><span class="custom-danger-btn remove-image-pin">X</span></a>
                                    @endif

                                    @if($data->photo_s3_key != '')

                                    <video class="fullimage fullimage-custom" controls>
                                         <source src="{{$data->media_url}}" type="video/mp4">
                                    </video>
                                    @else
                                    <video class="fullimage fullimage-custom" controls>
                                         <source src="{{url('public/services/images/'.$data->media_url)}}" type="video/mp4">
                                    </video>
                                    
                                    @endif
                                </div>
                            @elseif($data->media_type == 'doc')

                                <div class=" col-md-2 jobImg imgdiv_{{$data->secret}}">
                                        @if($data->photo_s3_key != '')
                                             <a href="{{route('remove_media_job',$data->secret)}}" class="docStyle"><span class="custom-danger-btn remove-image-pin">X</span></a>
                                        @endif

                                        @if($data->photo_s3_key != '')
                                      <img src="{{url('public/frontend/images/imgicon.png')}}" class="fullimage fullimage-custom">
                                        @else
                                        <a href="{{url('public/services/images/'.$data->media_url)}}">Document file</a>
                                        
                                        @endif
                                    </div>
                            
                            @elseif($data->media_type == 'pdf')

                                <div class=" col-md-2 jobImg imgdiv_{{$data->secret}}">

                                        @if($data->photo_s3_key != '')
                                             <a href="{{route('remove_media_job',$data->secret)}}" class="docStyle"><span class="custom-danger-btn remove-image-pin">X</span></a>
                                        @endif

                                        @if($data->photo_s3_key != '')
                                        
                                          <img src="{{url('public/frontend/images/imgicon.png')}}" class="fullimage fullimage-custom">
                                        @else
                                        <a href="{{url('public/services/images/'.$data->media_url)}}">Pdf file</a>
                                        
                                        @endif
                                    </div>
                            
                            @elseif($data->media_type == 'zip')

                                <div class=" col-md-2 jobImg imgdiv_{{$data->secret}}">
                                        @if($data->photo_s3_key != '')
                                             <a href="{{route('remove_media_job',$data->secret)}}" class="docStyle"><span class="custom-danger-btn remove-image-pin">X</span></a>
                                        @endif

                                        @if($data->photo_s3_key != '')
                                        
                                         <img src="{{url('public/frontend/images/imgicon.png')}}" class="fullimage fullimage-custom">
                                        
                                        @else
                                        
                                            <a href="{{url('public/services/images/'.$data->media_url)}}">Zip file</a>
                                        
                                        @endif
                                </div>
                             @endif
                            
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>    
       
</section>

<!-- End content with Sidebar -->
@endsection
@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">

<style>
</style>
@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script src="{{url('public/frontend/assets/js/dropzone.js')}}" type="text/javascript"></script>
<script src="{{url('public/frontend/assets/js/validate.min.js')}}"></script>
<script type="text/javascript">

    var editor='';
    var checkError=0;

    $(document).ready(function () {
        $('#tags').tagsinput({
            maxTags: 3
        });
    });

     /* Replace With CKEditor */
     ClassicEditor.create( document.querySelector( '#textarea_ck' ) )
    .then( newEditor => {
        order_note_editor = newEditor;
        order_note_editor.model.document.on( 'change:data', ( evt, data ) => {
            if(order_note_editor.getData() == ''){
                $('.getErrorDetail').html('<small class="dangerColor" style="">Please enter a value</small>');
            }else{
                $('.getErrorDetail').html('');
            }
        });
    })
    .catch( error => {
        console.error( error );
    });
    /* END Replace With CKEditor */

    var errorFiles = [];
    var check=0;
 
    Dropzone.options.imagedropzone = {
        maxFilesize: 5,
        acceptedFiles: "jpeg,.jpg,.png,.gif,.pdf,.doc,.zip,.mp4,.docx",
        uploadMultiple: false,
        parallelUploads: 50,
        paramName: "image",
        addRemoveLinks: true,
        autoProcessQueue: false,

        dictFileTooBig: 'File is larger than 5MB',
        timeout: 10000,
        init: function () {
            var msg = 'Maximum File Size 5MB';
            $('#imagedropzone .dz-message').append('<br><p class="text-secondary">('+msg+')</p>');

                this.on("error", function(file, response) {
                    $('.getErrorUpload').html('<small class="text-danger" style="">'+response+'</small>');
                        var countCheck = myDropzone.files.length;
                        checkError = checkError+1;
                        errorFiles.push(file.name);
                });
            
            myDropzone = this;
            $(document).on('click','#draft',function(e){
                $('#status').val('draft');
                
                var count= myDropzone.files.length;
                if(count > 0)
                {
                    if(errorFiles.length == 0)
                    {
                        var minPrice=$('#min_price').val();
                        var maxPrice=$('#max_price').val();
                            if(parseInt(minPrice) < parseInt(maxPrice))
                                {
                                    e.preventDefault();
                                    myDropzone.processQueue(); 
                                }      
                                else
                                {
                                    $('.getErrorPrice').html('<small class="dangerColor" style="">Please enter price more than '+minPrice+'</small>');
                                }       
                        }   
                }
                else
                {
                    if(errorFiles.length == 0)
                    {
                        submitUrl();
                    }
                }
                

            });

                $(document).on('click','#publish',function(e){
                $('#status').val('active');
                
                var count= myDropzone.files.length;
                if(count > 0)
                {
                    if(errorFiles.length == 0)
                    {
                        var minPrice=$('#min_price').val();
                        var maxPrice=$('#max_price').val();
                            if(parseInt(minPrice) < parseInt(maxPrice))
                                {
                                    e.preventDefault();
                                    myDropzone.processQueue(); 
                                }      
                                else
                                {
                                    $('.getErrorPrice').html('<small class="dangerColor" style="">Please enter price more than '+minPrice+'</small>');
                                }                                      
                    }
                }
                else
                {
                    if(errorFiles.length == 0)
                    {
                        submitUrl();
                    }
                }
                

            });
                myDropzone.on("removedfile", function(file){
                
                errorFiles.remove(file.name);
                if(errorFiles.length == 0)
                {
                    $('.getErrorUpload').html('');
                    checkError = 0;                             
                }
            });



                myDropzone.on("queuecomplete", function() {
                if(checkError == 0)
                {
                    submitUrl();
                }
            });
        },
        success: function (file, done) {
                
                $('.imgSet').append(done.data);
        },
        
    };

    Array.prototype.remove = function() {
        var what, a = arguments, L = a.length, ax;
        while (L && this.length) {
            what = a[--L];
            while ((ax = this.indexOf(what)) !== -1) {
                this.splice(ax, 1);
            }
        }
        return this;
    };       

      $('#max_price').focusout(function(){
        if(parseInt($('#min_price').val().length) < parseInt($(this).val().length))
         {
             $('.getErrorPrice').html('');
         }
     }); 

     $('#tags').focusout(function(){
        var getTag=$(this).val();

        if(getTag != null && getTag != '' && getTag != ' ')
        {
            $('.getErrorTag').html('')   
        }
     });

    function submitUrl()
    {
        var getTag=$('#tags').val();
        var getDetail = order_note_editor.getData();
        var minPrice=$('#min_price').val()? $('#min_price').val() : 0;
        var maxPrice=$('#max_price').val()? $('#max_price').val() : 0;
        var title=$('#job_title').val().length;
        var price_min=$('#min_price').val().length;
        var price_max=$('#max_price').val().length;
                 
        if(parseInt(price_max) > 0)
        {
            if(parseInt(minPrice) > parseInt(maxPrice))
             {
                 $('.getErrorPrice').html('<small class="dangerColor" style="">Please enter price more than '+minPrice+'</small>');
             }   
        }  

        if(getTag == null || getTag == '' || getTag == ' ')
        {
            $('.getErrorTag').html('<small class="dangerColor" style="">Please enter a value</small>');
        }

        if(order_note_editor.getData() == ''){
            order_note_editor.editing.view.focus();
            $('.getErrorDetail').html('<small class="dangerColor" style="">Please enter a value</small>');
        }

        if(getTag == null || getTag == '' || getTag == ' ' || order_note_editor.getData() == null || order_note_editor.getData() == '' || order_note_editor.getData() == ' ' || parseInt(minPrice) > parseInt(maxPrice) )
        {
            return false;
        }
        
        var data1 = $('#frmServiceOverview1').bootstrapValidator('revalidateField', 'subcategory_id');
        var data2 =$('#frmServiceOverview1').bootstrapValidator('revalidateField', 'job_title');
        var data3 =$('#frmServiceOverview1').bootstrapValidator('revalidateField','category_id');
         
        var checkValid = $('#frmServiceOverview1').valid();
        if(checkValid == true)
        {
            $('#frmServiceOverview1')[0].submit();    
            $('#publish').attr('disabled',true);
            $('#draft').attr('disabled',true);
        }
         
    }

    function isNumber(evt, element) {

        var charCode = (evt.which) ? evt.which : event.keyCode

        if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // “-” CHECK MINUS, AND ONLY ONE.
            (charCode != 46 || $(element).val().indexOf('.') != -1) &&      // “.” CHECK DOT, AND ONLY ONE.
            (charCode < 48 || charCode > 57))
            return false;

        return true;
    }  

  $(document).ready(function() {
        $('#min_price').keypress(function (event) {
            return isNumber(event, this)
        });
         $('#max_price').keypress(function (event) {
            return isNumber(event, this)
        });
    });

   
    
    $('#category_id').on('change', function (e) {
            $('#subcategory_id').val('');
            $('#frmServiceOverview1').bootstrapValidator('revalidateField', 'subcategory_id');
            e.preventDefault();
            $.ajax({
                url: '{!! route('get_subcategory') !!}',
                type: 'post',
                data: {'_token': _token, 'category_id': this.value},
                dataType: 'json',
                success: function (data, status) {
                    $('#subcategory_id').html('<option value="">Select Sub Category</option>');
                    for (var i=0; i<data.subcategory.length; i++) {
                        var row = $('<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>');
                        $('#subcategory_id').append(row);
                    }
                },
                error: function (xhr, desc, err) {
                    //console.log(xhr);
                    //console.log("Details: " + desc + "\nError:" + err);
                }
            });
        });


</script>

@endsection