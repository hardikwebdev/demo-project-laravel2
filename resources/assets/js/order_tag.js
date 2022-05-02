$(document).ready(function () {
    update_all_tag_fns();
    set_filter_tag_details();
    filter_tag_add_btn_fn();
    deselect_all_filter_tags_btn_fn();
});

function load_div(id, callback=false) {
    $(id).load(window.location.href + ' '+id+' > div', function() {
        if(callback != false){
            callback();
        }
    });
}

function set_filter_tag_details() {
    /* set css for tags for filter */
    if(total_filter_tags.length > 0) {
        /* first remove css from all */
        $('.filter_tag_option_class').addClass('bg-dark-white text-color-6');
        $('.filter_tag_option_class').removeClass('bg-primary-blue text-white');
        total_filter_tags.forEach(element => {
            $('#filter_tag_option'+element).removeClass('bg-dark-white text-color-6');
            $('#filter_tag_option'+element).addClass('bg-primary-blue text-white');
        });
    } else {
        $('.filter_tag_option_class').addClass('bg-dark-white text-color-6');
        $('.filter_tag_option_class').removeClass('bg-primary-blue text-white');
    }

    /* set total number of added tags for filter */
    if(total_filter_tags.length > 1) {
        $('#total_number_of_filter_tags').text('+'+(total_filter_tags.length - 1));
    } else {
        $('#total_number_of_filter_tags').text('');
    }

    /* set text of added tags for filter */
    if(total_filter_tag_names.length > 1) {
        var tag_name_to_show = total_filter_tag_names[0];
        if(tag_name_to_show.length > 15) {
            tag_name_to_show = tag_name_to_show.substr(0,15) + '..';
        }
        $(".filter_tag_name_list").text(tag_name_to_show+",...");
    } else if(total_filter_tag_names.length == 1) {
        var tag_name_to_show = total_filter_tag_names[0];
        if(tag_name_to_show.length > 15) {
            tag_name_to_show = tag_name_to_show.substr(0,15) + '..';
        }
        $(".filter_tag_name_list").text(tag_name_to_show);
    } else {
        $(".filter_tag_name_list").text("Select Tag");
    }
}

function update_all_tag_fns(i=0) {
    bind_select2_dropdown();
    add_tag_in_order_btn_fn();
    remove_tag_from_order_btn_fn();
    clear_all_tags_from_order_btn_fn();
    search_tag_btn_fn();
    clear_tag_dropdown_fn();
    add_tag_dropdown_fn();
}

function bind_select2_dropdown() {
    $(".search_tag").select2({
        tags: true,
        ajax: { 
            url: get_tags_list_route,
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    '_token': _token,
                    searchTerm: params.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response.tags
                };
            },
            cache: true
        },
    });
}

function add_tag_in_order_btn_fn() {
    $('.add_tag_in_order_btn').unbind('click').bind('click', function() {
        var orderid = $(this).data('orderid');
        var tagid = $(this).data('tagid');
        $.ajax({
            type: "POST",
            url: add_order_into_tag_route,
            data: {
                "_token": _token,
                "orderid": orderid,
                "tagid": tagid,
                "tagname":"",
            },
            success: function (data) {
                if(data.status == 'success') {
                    is_loader = false;
                    toastr.success(data.message, "Success");
                    /* load_div('#tag_list_area'+orderid, function() {
                        update_all_tag_fns();
                    }); */
                    var html = '';
                    data.MostUsedOrderTags.forEach(element => {
                        if(data.addedTags.includes(element.id)) {
                            var classname = "bg-primary-blue text-white";
                        } else {
                            var classname = "bg-dark-white text-color-6";
                        }
                        html += '<a href="javascript:void(0)" data-orderid="'+orderid+'" data-tagid="'+element.secret+'" class="add_tag_in_order_btn">';
                        html += '<span class="'+classname+' border-gray-1px border-radius-3px font-weight-bold font-10 px-2 py-1 mr-1 mt-1 cursor-pointer tag_break">'+element.tag_name+'</span>';
                        html += '</a>';
                    });
                    $('#tag_list_area'+orderid).html(html);
                    update_all_tag_fns();
                } else {
                    toastr.error(data.message, "Error");
                }
            }
        });
    });
}

function remove_tag_from_order_btn_fn() {
    $('.remove_tag_from_order_btn').unbind('click').bind('click', function() {
        var orderid = $(this).data('orderid');
        var tagid = $(this).data('tagid');
        var __this = $(this);
        $.ajax({
            type: "POST",
            url: remove_tag_from_order_route,
            data: {
                "_token": _token,
                "orderid": orderid,
                "tagid": tagid
            },
            success: function (data) {
                if(data.status == 'success') {
                    toastr.success(data.message, "Success");
                    is_loader = false;
			        $('#status_form_Search').trigger('submit');
                    // load_div('#tag_section_area', function() {
                    //     update_all_tag_fns();
                    // });
                }
            }
        });
    });
    $('.remove_added_tag_from_order_btn').unbind('click').bind('click', function() {
        var orderid = $(this).data('orderid');
        var tagid = $(this).data('tagid');
        var __this = $(this);
        $.ajax({
            type: "POST",
            url: remove_tag_from_order_route,
            data: {
                "_token": _token,
                "orderid": orderid,
                "tagid": tagid
            },
            success: function (data) {
                if(data.status == 'success') {
                    toastr.success(data.message, "Success");
                    var count = $('#total_added_tag_counter'+orderid).text();
                    count = parseInt(count);
                    if(count > 0) {
                        $('#total_added_tag_counter'+orderid).text(count - 1);
                    }
                    $('#added_tag_span'+tagid).remove();
                }
            }
        });
    });
}

function filter_tag_add_btn_fn() {
    $('.filter_tag_add_btn').unbind('click').bind('click', function() {
        var tagid = $(this).data('tagid');
        var tagname = $(this).data('tagname');
        if(!total_filter_tags.includes(tagid)) {
            total_filter_tags.push(tagid);
            total_filter_tag_names.push(tagname);
        } else {
            var index = total_filter_tags.indexOf(tagid);
            if (index > -1) {
                total_filter_tags.splice(index, 1);
            }
            var index2 = total_filter_tag_names.indexOf(tagname);
            if (index2 > -1) {
                total_filter_tag_names.splice(index2, 1);
            }
        }
		$('#total_filter_tags_id').val(total_filter_tags);
		$('#order_paginate').val(1);
        $('#status_form_Search').trigger('submit');
        set_filter_tag_details();
    });
}

function clear_all_tags_from_order_btn_fn() {
    $('.clear_all_tags_from_order_btn').unbind('click').bind('click', function() {
        var orderid = $(this).data('orderid');
        $.ajax({
            type: "POST",
            url: clear_all_tags_from_order_route,
            data: {
                "_token": _token,
                "orderid": orderid,
            },
            success: function (data) {
                if(data.status == 'success') {
                    toastr.success(data.message, "Success");
			        $('#status_form_Search').trigger('submit');

                    // load_div('#tag_section_area', function() {
                    //     update_all_tag_fns();
                    // });
                } else {
                    toastr.error(data.message, "Error");
                }
            }
        });
    });
}

function search_tag_btn_fn() {
    $('.search_tag_btn').unbind('click').bind('click', function() {
        var orderid = $(this).data('orderid');
        var tagid = $("#search_tag"+orderid).val();
        var selected_text = $("#search_tag"+orderid).find(":selected").text();
        var tagname = '';
        if(selected_text == tagid) {
            tagname = tagid;
            tagid = 0;
        }
        $.ajax({
            type: "POST",
            url: add_order_into_tag_route,
            data: {
                "_token": _token,
                "orderid": orderid,
                "tagid": tagid,
                "tagname": tagname,
            },
            success: function (data) {
                if(data.status == 'success') {
                    toastr.success(data.message, "Success");
                    is_loader = false;
			        $('#status_form_Search').trigger('submit');

                    // load_div('#tag_section_area', function() {
                    //     update_all_tag_fns();
                    // });

                    var html = '';
                    data.OrderTags.forEach(element => {
                        html += '<a href="javascript:void(0)" class="filter_tag_add_btn" data-tagid="'+element.id+'" data-tagname="'+element.tag_name+'">';
                        html += '<span class="bg-dark-white border-gray-1px border-radius-3px text-color-6 font-weight-bold font-10 px-2 py-1 mr-1 mt-1 cursor-pointer filter_tag_option_class tag_break" id="filter_tag_option'+element.id+'">'+element.tag_name+'</span>';
                        html += '</a>';
                    });
                    $('#filter_tag_lists_id').html(html);
                    deselect_all_filter_tags_btn_fn();
                    filter_tag_add_btn_fn();
                } else {
                    toastr.error(data.message, "Error");
                }
            }
        });
    });
}

function clear_tag_dropdown_fn() {
    $('.clear_tag_dropdown').on('hide.bs.dropdown', function () {
        $('#status_form_Search').trigger('submit');
        // load_div('#tag_section_area', function() {
        //     update_all_tag_fns();
        // });
    });
}

function add_tag_dropdown_fn() {
    $('.add_tag_dropdown').on('hide.bs.dropdown', function () {
        $('#status_form_Search').trigger('submit');
        // load_div('#tag_section_area', function() {
        //     update_all_tag_fns();
        // });
    });
}

function deselect_all_filter_tags_btn_fn() {
    $('#deselect_all_filter_tags_btn').unbind('click').bind('click', function() {
        total_filter_tags = [];
	    total_filter_tag_names = [];
        set_filter_tag_details();
		$('#total_filter_tags_id').val(total_filter_tags);
        $('#order_paginate').val(1);
        $('#status_form_Search').trigger('submit');
    });
}