@extends('layouts.frontend.main')
@section('pageTitle','demo - Create Job')
@section('content')
@section('topcss')
<link href="{{url('public/frontend/assets/css/dropzone.css')}}" rel="stylesheet">
@endsection
<!-- Display Error Message -->
@include('layouts.frontend.messages')

<!-- header Title -->
<section class="sub-header product-detail-header m-purple-bg ">
    <div class="container">
        <div class="m-card-img m-card-img-bottom  pb-4">
            <img src="{{url('public/frontend/images/user-02.jpg')}}" alt="" class="img-fluid m-img-roundbr img-round d-inline">
            <div class="m-card-contet ml-4">
                <h5 class="text-white ">Create A New Job Opening</h5>
                <!-- <p class="text-white mb-0">Settings <i class="fas fa-cog ml-1"></i></p> -->
            </div>
        </div> 
    </div>
</section>
<!-- End header Title -->


<!-- Instructions Section -->
<section class="jobpostinstructions">
    <div class="container">
        <div class="row align-items-center">
            <h3>Tell Us What You Need Done</h3>
            <p>Fill out the form here and tell us what you need to get done.  We'll connect you with our community of talented freelancers who can submit bids and you'll be able to award your job to the one you feel is the best fit.</p>
        </div>    
    </div>
</section>
<!-- End Instructions Section -->




<!-- content with Sidebar -->

<section class="product-block pt-2 mt-5">
    <div class="container">

        <div class="row ">
            
            <div class="col-lg-7 jobentryform">
                 {{ Form::open(['route' => ['create_job'], 'method' => 'POST', 'id' => 'frmServiceOverview1']) }}
                 <input type="hidden" name="_token" value="{{ Session::token() }}">
                <div class="">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <!--Input job title-->
                                <label for="fullname">Choose A Name For Your Job Opening</label>
                                <p>Choose something descriptive that will attract offers</p>
                                <input type="text" id="job_title" name="job_title" class="form-control" value="" autocomplete="off" placeholder="Something like 'Graphic designer to help illustrate childrens story book'" data-bv-field="job_title">
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
                                <label>Choose A Category</label>

                                {{ Form::select('category_id', [''=>'Select Category']+$categories,null,['class' => 'form-control','id'=>'category_id', 'data-bv-field' => 'category_id']) }}     
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <!--Enter sub category-->
                                <label>Choose A Sub Category</label>
                                <select id="subcategory_id" class="form-control" name="subcategory_id" data-bv-field="subcategory_id">
                                    <option value="" selected="selected">Select Sub Category</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group m-taginput-bg">
                                  <!--Enter tags for job-->
                                <label>Add Tags</label>
                                <input type="text" required="true" id="tags" name="tags" class="form-control" autocomplete="off" placeholder="">
                                <small class="dangerColor"><i>Maximum 3 tags allowed</i></small><br>
                                <span class="getErrorTag"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group m-green-input">
                                <!--Enter minimum price-->
                                <label class="mb-2 m-text-green">Minimum Price</label>

                                 <input type="text" id="min_price" name="min_price" class="form-control" value="" autocomplete="off" placeholder="The lowest you want to pay for this job" data-bv-field="min_price">
                                 <span class="getErrorPriceMin"></span>

                                {{--<select class="price-by form-control" name="min_price" id="price_by">
                                    <option value="100" data-usd="USD" >100 <span class="pull-right">USD</span></option>
                                    <option value="200" data-usd="USD" >200 <span class="pull-right">USD</span></option>
                                    <option value="300" data-usd="USD" >300 <span class="pull-right">USD</span></option>
                                    <option value="400" data-usd="USD">400 <span class="pull-right">USD</span></option>
                                </select>--}}
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group m-green-input">
                                <!--Enter maximum price-->
                                <label class="mb-2 m-text-green">Maximum Price</label>

                                 <input type="text" id="max_price" name="max_price" class="form-control" value="" autocomplete="off" placeholder="The most you want to pay for this job" data-bv-field="max_price">
                                 <span class="getErrorPrice"></span>

                                {{--<select class="price-by form-control" name="max_price" id="price_high">
                                    <option value="100" data-usd="USD" >100 <span class="pull-right">USD</span></option>
                                    <option value="200" data-usd="USD" >200 <span class="pull-right">USD</span></option>
                                    <option value="300" data-usd="USD" >300 <span class="pull-right">USD</span></option>
                                    <option value="400" data-usd="USD">400 <span class="pull-right">USD</span></option>
                                </select>--}}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <!--Enter Description-->
                                <label class="mb-2 ">Tell Us About This Project</label> 
                                <p>Start with some information about yourself or your business, and include a good overview of what you need done.  The more information about what you are looking for the better, so be as descriptive as possible.</p>
                                <div class="ckeditor_data">
                                    <textarea rows="4" cols="100" name="description" class=" form-control" id="textarea_ck" ></textarea>
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
                                <div class="fallback">
                                    <input name="file_upload" class="inputfile" multiple />
                                </div>
                               
                            </form>
                             <span class="getErrorUpload"></span>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <button  type="button" id="draft" class="btn d-inline mbtn-blue m-btn-blue m-brd-rad-15 pr-4 pl-3"><i class="fas fa-save mr-2"></i> Save Draft</button>
                         <button  type="button" id="publish" class="btn d-inline m-btn-green m-wdth-200 m-brd-rad-15"> Publish Job Opening <i class="fas fa-chevron-right pull-right mt-1"></i></button>
                    </div>
                </div>
            </div>
        </div>    
        {{--<div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <!--Enter Description-->
                    <label class="mb-2 ">About This Project</label> 
                    <div class="ckeditor_data">
                        <textarea rows="4" cols="100" class=" form-control" id="textarea_ck" ></textarea>
                        <span class="getErrorDetail"></span>

                        
                    </div>
            </div>
        </div>--}}
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
        job_description = newEditor;
        job_description.model.document.on( 'change:data', ( evt, data ) => {
            var descriptions =  job_description.getData();
            if(descriptions==''){
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
            
            myDropzone = this;
                this.on("error", function(file, response) {
                    $('.getErrorUpload').html('<small class="text-danger" style="">'+response+'</small>');
                    var countCheck = myDropzone.files.length;
                        checkError = checkError+1;
                        errorFiles.push(file.name);

                    
                });
            $(document).on('click','#draft',function(e){
                $('#status').val('draft');
                $('#frmServiceOverview1').data('bootstrapValidator').validate();
                if($('#frmServiceOverview1').data('bootstrapValidator').isValid()){
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
                                return false;
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
            }

            });

                $(document).on('click','#publish',function(e){
                $('#status').val('active');
                $('#frmServiceOverview1').data('bootstrapValidator').validate();
                if($('#frmServiceOverview1').data('bootstrapValidator').isValid()){
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
                                return false;
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
        if(parseInt($('#min_price').val()) < parseInt($(this).val()))
         {
             $('.getErrorPrice').html('');
         }
    });


    function submitUrl()
    {
        var getDetail=$('#textarea_ck').val();
        var getTag=$('#tags').val();
        var data1 = $('#frmServiceOverview1').bootstrapValidator('revalidateField', 'subcategory_id');
        var data2 =$('#frmServiceOverview1').bootstrapValidator('revalidateField', 'job_title');
        var data3 =$('#frmServiceOverview1').bootstrapValidator('revalidateField','category_id');
        var data4 =$('#frmServiceOverview1').bootstrapValidator('revalidateField','min_price');
        var data5 =$('#frmServiceOverview1').bootstrapValidator('revalidateField','max_price');
        var title=$('#job_title').val().length;
        var price_min=$('#min_price').val().length;
        var price_max=$('#max_price').val().length;
        var minPrice=$('#min_price').val();
        var maxPrice=$('#max_price').val();
        
        $('.getErrorTag').html('');
        $('.getErrorPrice').html('');
        
        
        if(getTag == null || getTag == '' || getTag == ' ')
        {
            $('.getErrorTag').html('<small class="dangerColor" style="">Please enter a value</small>');
            return false
        }

        if(parseInt(maxPrice))
        {
            if(parseInt(minPrice) >= parseInt(maxPrice))
            {
                $('.getErrorPrice').html('<small class="dangerColor" style="">Please enter price more than '+minPrice+'</small>');
                return false;
            }
        }

        if(job_description.getData() == ''){
            job_description.editing.view.focus();
            $('.getErrorDetail').html('<small class="dangerColor" style="">Please enter a value</small>');
            return false;
        }

        // $('#description').val(editor.getData());
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

        if (
            (charCode != 45 || $(element).val().indexOf('-') != -1) &&      // “-” CHECK MINUS, AND ONLY ONE.
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