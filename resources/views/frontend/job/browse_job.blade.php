@extends('layouts.frontend.main')
@section('pageTitle','demo - Browse Job')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')
<!-- content with Sidebar -->
<section class="product-block pt-0">
	<div class="container">
		<div class="row ">
            <!-- Left Sidebar -->
            <div class="col-lg-4 job-listing-sidebar">
                <!-- Display filters to find out job according to requirement-->
                <form method="get" id="searchJob" action="{{ route('browse.jobSerach')}}">
                    <input type="hidden" name="_token" value="{{Session::token()}}">
                    <input type="hidden" name="offset" id="hid_offset" value="{{$limit}}">
                    <div class="side-layout m-bg-gray m-brd-rad-15">
                        <div class="m-brdr-btm m-category">
                            <div class="siderbar-lay  ">
                                <h5 class="m-fw-normal">Job categories</h5> 
                            </div>
                            <!-- Display categories-->
                            <ul class="m-checkbox-filter mt-3">
                                @if(count($categories) > 0)
                                    @foreach($categories as $category)
                                    <li class="category-block">
                                        
                                        <input type="checkbox" @if(request()->category_search != null)
                                        @if(request()->category_search == $category->id) checked @endif @endif name="cat[]" value="{{$category->id}}" class="cat-checkbox category_{{$category->id}}" data-id="{{$category->id}}">
                                        <a href="javascript:void(0)" id="1" data-toggle="collapse" data-target="#collapse_{{$category->id}}" aria-expanded="true" aria-controls="collapse_{{$category->id}}" class="default-gray ml-2"><strong>{{$category->category_name}}</strong><span class="pull-right"><span class="">({{($category->jobs)?$category->jobs->count():0}})</span><i class="fas fa-chevron-down ml-2"></i></span></a>
                                        <div class="collapse ml-2" id="collapse_{{$category->id}}">
                                            @if(count($category->subcategory) > 0)
                                            <ul>
                                                <!-- Display sub categories-->
                                                @foreach($category->subcategory as $sub_category)
                                                <li class="subcategory_block">
                                                    <input type="checkbox" data-cat="{{$category->id}}" name="subcat[]"  @if(request()->category_search != null)
                                        @if(request()->category_search == $category->id) checked @endif @endif value="{{$sub_category->id}}" class="subcat-checkbox subcategory_{{$category->id}} subcategoryid_{{$sub_category->id}}">
                                                    <a href="javascript:void(0)" data-id="{{$sub_category->id}}" id="2" class=" subcatLable default-gray">{{$sub_category->subcategory_name}}
                                                    </a>
                                                </li>
                                                
                                                @endforeach
                                            </ul>
                                            @endif
                                        </div>
                                    </li>
                                    @endforeach
                                @endif
                               
                            </ul>
                        </div>
                        <!-- Skill search -->
                        <div class="m-brdr-btm m-skill mt-3">
                            <div class="siderbar-lay ">
                                <!-- Input skill to search-->
                                <h5 class="m-fw-normal">Skills</h5> 
                            </div>
                            <div class="m-skill-search m-taginput-bg mt-2">
                                <input type="text" id="skills" name="skills" class="form-control" value="" placeholder="" data-role="tagsinput" placeholder="Search Skill">
                            </div>
                        </div>
                        <!-- Skill search -->
                        <!-- Price Range -->

                        <div class=" m-pricerange mt-3  m-brdr-btm">
                            <!--input price range for job-->
                            <div class="siderbar-lay ">
                                <h5 class="m-fw-normal">Price ($)</h5> 
                            </div>
                            <div class="row">
                                <div class="col">
                                    <input type="number" name="min_price" min=0 max="9900" oninput="validity.valid||(value='0');" id="min_price" class="form-control" />
                                </div>
                                <div class="col pl-0">
                                    <input type="number" name="max_price" min=0 max="10000" oninput="validity.valid||(value='10000');" id="max_price" class="form-control" />
                                </div>
                            </div>
                            <div id="slider-range" class="price-filter-range" name="rangeInput"></div>
                        </div>
                        <!--End Price Range -->
                        <!-- Price Range -->
                        <div class="m-time m-brdr-btm mt-3 d-block">
                            <!--Select job creating time filter-->
                            <div class="siderbar-lay">
                                <h5 class="m-fw-normal">Time <div class="pull-right d-inline"><input type="radio" class="anyCheck" checked="true" name="time" value="any_time"><span class="anyLabel"> Any Time</span></div></h5> 
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="m-checkbox time-bg m-brd-rad-5">
                                        <input type="radio" class="commanCheck todayCheck" name="time" value="today"> <span class="todayLabel">Today</span>
                                    </div>
                                </div>
                                <div class="col pl-0">
                                    <div class="m-checkbox time-bg m-brd-rad-5">
                                        <input type="radio" class="commanCheck Check3" name="time" value="3"> <span class="Label3">3 Days</span>
                                    </div>
                                </div>
                                <div class="col pl-0">
                                    <div class="m-checkbox time-bg m-brd-rad-5">
                                        <input type="radio" class="commanCheck Check5" name="time" value="5"><span class="Label5"> 5 Days</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="m-checkbox time-bg m-brd-rad-5">
                                        <input type="radio" class="commanCheck Check10" name="time" value="10"> <span class="Label10"> 10 Days</span>
                                    </div>
                                </div>
                                <div class="col pl-0">
                                    <div class="m-checkbox time-bg m-brd-rad-5">
                                        <input type="radio" class="commanCheck Check15" name="time" value="15"> <span class="Label15"> 15 Days</span>
                                    </div>
                                </div>
                                <div class="col pl-0">
                                    <div class="m-checkbox time-bg m-brd-rad-5">
                                        <input type="radio" class="commanCheck Check20" name="time" value="20"> <span class="Label20">20 Days</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--End Price Range -->
                        <button type="button" data-inline="true" value="Submit" class="btn m-btn-blue m-brd-rad-15 pr-4 pl-3 m-btn-full mt-3 searchBtn">Filter Now</button>
                    </div>    
                </form>
            </div>
            <!-- End Sidebar -->
			<div class="col-lg-8">
                <div class="m-brdr-btm m-ptb-15 no_bottom_border">
                    <!--Display result of total job found-->
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="mb-0"><span id="countRecord">{{$total_result}}</span> Results</p>
                        @if(Auth::check())
                            @if(Auth::user()->parent_id == 0)
                                <a href="{{route('jobs.create')}}" class="btn browse_post_job_btn"> Post a Job </a>
                            @endif
                        @else
                            <a href="{{url('login')}}?jobAdd=1" class="btn browse_post_job_btn"> Post a Job </a>
                        @endif
                        {{-- <a href="javascript:;"><img src="{{url('public/frontend/images/horizontal.png')}}" alt="" class="img-fluid text-primary ml-1 m-width-15"></a>
                        <a href="javascript:;"><img src="{{url('public/frontend/images/vertical.png')}}" alt="" class="img-fluid text-primary ml-1 m-width-15"></a> --}}
                    </div>
                </div>
                <!-- Bids Lists -->
                <div class="dynamicDiv">
                    <!--Display all the jobs here-->
                    @include('frontend.job.dyanamic_section')
                    @php
                    $hide_class = 'hide';
                    if($total_result == 0){
                        $hide_class = '';
                    }
                    @endphp
                    <!--Display message when no records founds-->
                    <div class="frm-store-search {{$hide_class}}">
                        <center><label>No records found.</label></center>
                    </div>
                   {{-- @php
                        $hide_class = '';
                        if($total_result <= $limit)
                        {
                            $hide_class = 'hide';
                        }
                    @endphp--}}
                    {{-- <div class="row">
                        <div class="col-md-4">
                        </div>    
                        <div class="col-md-4">
                            <div class="loadmorediv {{$hide_class}}">
                                <a href="javascript:;" class="btn btn-info loadMore"> Load More <i class="fa fa-spinner fa-spin pl-2 pt-1"></i></a>
                            </div>
                        </div>
                    </div>--}}
                </div>
                <div class="col-sm-12 text-center">
                    <img src="{{url('public/frontend/assets/img/filter-loader.gif')}}" class="ajax-load"> 
                </div>
            </div>
		</div>    
	</div>
</section>
<!-- End content with Sidebar -->

@endsection

@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>
@endsection
@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>
<script type="text/javascript">

    $(document).on('click','.todayLabel',function(){
        
        $('.todayCheck').prop('checked',true);
        
        $('#hid_offset').val("0");
        searchData();
    });

    $(document).on('click','.subcatLable',function(){
        var id=$(this).data('id');

        if($('.subcategoryid_'+id).prop('checked') == true)
        {
            $('.subcategoryid_'+id).prop('checked',false);
        }
        else
        {
            $('.subcategoryid_'+id).prop('checked',true);
        }
        $('#hid_offset').val("0");
        searchData();
    });

    $(document).on('click','.anyLabel',function(){
        $('.anyCheck').prop('checked',true);
        $('#hid_offset').val("0");
        searchData();
    });

    $(document).on('click','.Label3',function(){
        $('.Check3').prop('checked',true);
        $('#hid_offset').val("0");
        searchData();
    });

    $(document).on('click','.Label5',function(){
        $('.Check5').prop('checked',true);
        $('#hid_offset').val("0");
        searchData();
    });
    $(document).on('click','.Label10',function(){
       $('.Check10').prop('checked',true);
        $('#hid_offset').val("0");
        searchData();
    });
    $(document).on('click','.Label15',function(){
        $('.Check15').prop('checked',true);
        $('#hid_offset').val("0");
        searchData();
    });
    $(document).on('click','.Label20',function(){
        $('.Check20').prop('checked',true);
        $('#hid_offset').val("0");
        searchData();
    });

    $(document).on('change','.anyCheck',function(){
        $('.anyCheck').prop('checked',true);
        $('#hid_offset').val("0");
            searchData();
    });

    $(document).on('change','.cat-checkbox',function(){
        $this = $(this);
        var categoryId = $this.attr('data-id');
        
        if($this.is(':checked')) {
                $this.parents('.category-block').find('.subcategory_'+categoryId).prop('checked', true);
        } 
        else {
            $this.parents('.category-block').find('.subcategory_'+categoryId).prop('checked', false);
        }
    });
    $(document).on('change','.subcat-checkbox',function(){
         $this = $(this);
        var cat= $this.data('cat');
        // if($this.is(':checked')) {
        //     $this.parents('.category-block').find('.category_'+cat).prop('checked', true);
        // }
        // else
        // {
        //     $this.parents('.category-block').find('.category_'+cat).prop('checked', false);
        // }
       //  // 

       //  var imgcount=0;
       // var imgcheckedcount=0;
       // $('.subcategory_'+cat).each(function( index ,value) {      
       //    imgcount=imgcount+1;
       //    if($(this).prop('checked') == true)
       //    {
       //       imgcheckedcount=imgcheckedcount+1;
       //    }    

       //  if(imgcheckedcount == 0)
       //  {   
       //    $('.category_'+cat).prop('checked',false);
       //  }
       //  else
       //  {
       //    $('.category_'+cat).prop('checked',true);
          
       //  }

       
        
    });

    var count1=0;
    var count2=0;
    $('#slider-range').slider().bind('slidechange',function(event,ui){
        if(count1 == 0 )
        {
            count1 = count1 + 1;
        }
        else if(count2 == 0)
        {
            count2 = count2 + 1;
        }
        else
        {
            $('#hid_offset').val("0");        
            searchData(); 
        }
            
    });


    $('.fa-spinner').css('display','none');
    $(document).on('change','input',function(){
        $('#hid_offset').val("0");
       searchData();
    });

    $(document).on('click','.searchBtn',function(){
        $('#hid_offset').val("0");
        searchData();
    });

    function searchData(from_loadmore=false)
    {
        var data = $('#searchJob').serialize();
        $('#loadingGIF').show();
        
        $.ajax({
            url: '{{route("browse.job")}}',
            type: 'post',
            data: data,

            dataType: 'json',

          
            success: function (response) {
                page = 1;
                call_pagination=0;
                $('#loadingGIF').hide();
                $('.fa-spinner').css('display','none');

                if(response.success == true){

                        if(from_loadmore == true){

                            $('.dynamicDiv').append(response.html);
                        }else{
                            $('.dynamicDiv').html(response.html);
                            $('#countRecord').text(response.total_result);
                            if(response.total_result == 0){
                                $('.frm-store-search').removeClass('hide');
                            }else{
                                $('.frm-store-search').addClass('hide');
                            }
                        }

                        $('.loadmorediv a').removeClass('loader');
                        $('#hid_offset').val(response.offset);


                        if(response.display_loader == false){
                            $('.loadmorediv').addClass('hide');
                        }else{
                            $('.loadmorediv').removeClass('hide');
                        }
                    }else{
                        $('.loadmorediv').addClass('hide');
                          $('#countRecord').text(0);
                        if(from_loadmore == false){
                            $('.dynamicDiv').html('');
                            $('.frm-store-search').removeClass('hide');
                        }
                    }
            },
            error: function (xhr, desc, err) {
            }
        });
    }
    $(document).on("click",'.loadmorediv',function(){
        $('.fa-spinner').css('display','block');
        searchData(from_loadmore=true);
    });

    var page = 1;
    var call_pagination = 0;
    function loadMoreDataForJobs(page){
        $('#myOverlay').show();
        $('#loadingGIF').show();

        var url = "{{route('browse.job')}}";
        var data = $('#searchJob').serialize();
        data += '&page='+page;

        $.ajax({
            method:"get",
            url:url,
            async:false,
            data:data,
            beforeSend: function()
            {
                $('.ajax-load').show();
            }
        })
        .done(function(data)
        {
            if(data.success == false){
                $('.ajax-load').html("No more records found");
                call_pagination=1;
            }else {
                call_pagination=0;
            }
            $('.ajax-load').hide();
            $('.dynamicDiv').append(data.html);

            setTimeout(function () {
                $('#myOverlay').hide();
                $('#loadingGIF').hide();
            }, 500);

        })
        .fail(function(jqXHR, ajaxOptions, thrownError)
        {
            setTimeout(function () {
                $('#myOverlay').hide();
                $('#loadingGIF').hide();
            }, 500);
            console.log(thrownError);
            alert('server not responding...');
        });
    }

    /**Pegination on scroll  */
    $(window).scroll(function() {
        if($(window).scrollTop() + $(window).height() >= ($(document).height() - $('footer').height() )) {
            if(call_pagination == 0){
                page++;
                loadMoreDataForJobs(page);
            }
        }
    });
</script>
@endsection