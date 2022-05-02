@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Manage Tags')
@section('content')
<!-- @include('frontend.seller.header') -->


<!-- popular service -->
<section class="popular-services popular-tab-icon user-profile-tab">
    <div class="container p-0">
        <div class="row m-0 justify-content-center">
            <div class="col-lg-3">
                @include('frontend.seller.myprofile_tabs')
            </div>

            <div class="col-lg-8">
                <div class="popular-tab-item p-0">
                    <div class="profile-update tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="gigs" role="tabpanel" aria-labelledby="gigs">
                            <div class="popular-grid m-0">
                                @include('layouts.frontend.messages')
                                <div class="seller p-4 border-bottom">
                                    <div class="row m-0">
                                        <div class="col-12">
                                            Manage Tags
                                        </div>
                                    </div>
                                </div>

                                <div class="row px-4 mt-3 m-0">
                                    <div class="col-12">
                                        <label class="register-text-dark-black font-14">Add tag</label>
                                    </div>

                                    <div class="col-12">
                                        {{ Form::open(['route' => ['save_add_order_tag'], 'method' => 'POST', 'id' =>
                                            'create_order_tag']) }}
                                        <div class="row justify-content-between align-items-center border border-radius-8px p-2 m-0">
                                            <div class="col-12 col-sm-auto d-flex align-items-center">
    											<h6 class="register-text-dark-black font-14 font-weight-bold mb-0 text-nowrap mr-2">Tag name</h6> 
    											{{Form::text('tag_name',null, ["class"=>"form-control required", "placeholder"=>"NEW_TAG", "id"=>"tag_name"])}}
                                            </div>
                                            <div class="col-12 col-sm-auto mt-3 mt-sm-0">  
                                                {{Form::submit('Add New Tag', ["class"=>"send-request-buttom btn register-bg-dark-primary text-white font-14 px-3 py-2"])}}
                                            </div>
                                        </div>
                                        {{ Form::close() }}
                                    </div>
                                </div>

                                 
                                <div class="row px-4 mt-3 m-0 pb-4">
                                    <div class="col-12 table-responsive" id="tags_table">
                                        @include("frontend.order_tags.tags_table")
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


@endsection

@section('css')
<style>
    #add_tag_div .form-control {
        border: 1px solid gray !important;
    }

    #add_tag_div button {
        width: 50% !important
    }

    .tag_head tr {
        color: #aeaeae;
    }

    .tag_head tr th {
        border-bottom: 0px !important;
        border-top: 0px !important;
    }

    .clear_orders_btn {
        color: #47b5f8 !important;
    }

    .table_layout {
        table-layout: fixed;
    }

    .word_break {
        word-break: break-word;
    }

    .add_tag_btn {
        font-size: 14px;
        padding-left: 20px;
        padding-right: 20px;
    }
</style>
@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type='text/javascript'>
    $('document').ready(function () {
        $('.tag_form_class').hide();
        $('.tag_value_class').show();

        $(document).on('click', '.page-link', function(event){
            event.preventDefault(); 
            var page = $(this).attr('href');
            if (page!='' && page!=null) {
                $.ajax({
                    type: "GET",
                    url: page,
                    success: function (data) {
                        $('#tags_table').html(data);
                    }
                });
            }
        });

        $(document).on('click', '.add_tag_btn',function () {
            //$('#add_tag_div').removeClass('hide');
            //$('#add_tag_div').show();
        });

        $(document).on('click', '.rename_tag_btn',function () {
            var tagid = $(this).data('tagid');
            $('#tag_value' + tagid).hide();
            $('#tag_form' + tagid).show();
            $('#save_' + tagid).show();
            $(this).hide();
        });

        $(document).on('click','.save_tag_btn', function () {
            var tagid = $(this).data('tagid');
            var tagname = $('#tag_name_'+tagid).val();
            var url = $(this).data('url');
            $.ajax({
                type: "POST",
                url: url,
                data: {
                    "_token": "{{csrf_token()}}",
                    "id": $('#secret_'+tagid).val(),
                    "tag_name": tagname,
                },
                success: function (data) {
                    if (data.status == true) {
                        $('#save_' + tagid).hide();
                        $('#edit_' + tagid).show();
                        $('#tag_value' + tagid).show();
                        $('#tag_form' + tagid).hide();
                        $('#tag_name_list_' + tagid).html(tagname);
                        toastr.success(data.message);
                    }
                    else{
                        toastr.error(data.message);
                    }
                }
            });
              
        });

        $(document).on('click','.delete_tag_btn' ,function () {
            var url = $(this).data('url');
            var enc_id = $(this).data('secret');
            bootbox.confirm("Are you sure you want to delete this tag?", function (result) {
                if (result) {
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: {
                            "_token": "{{csrf_token()}}",
                            "enc_id": enc_id
                        },
                        success: function (data) {
                            if (data.status == 'success') {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });

        $(document).on('click', '.clear_orders_btn',function () {
            var url = $(this).data('url');
            var enc_id = $(this).data('secret');
            bootbox.confirm("Are you sure you want to remove all orders from this tag?", function (result) {
                if (result) {
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: {
                            "_token": "{{csrf_token()}}",
                            "enc_id": enc_id
                        },
                        success: function (data) {
                            if (data.status == 'success') {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection