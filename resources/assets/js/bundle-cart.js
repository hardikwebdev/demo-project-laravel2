$(document).ready(function () {
    $('.checkout_div').hide();
    load_all_extra();
    discount_coupon_click();
    remove_cart_click();
    checkout_btn_click();
    update_cart_quantity_clicks();
    remove_coupon_btn_click();
    

    $('.package_type').unbind('change').bind('change', function() {
        var id = $(this).data('cartid');
        var plan_id = $('#package_type'+id).val();
        var plan_price = $('#package_type'+id).find(":selected").data('price');
        $.ajax({
            type: "POST",
            url: update_cart_route,
            data: {
                "_token": _token,
                "id": id,
                "plan_id": plan_id
            },
            success: function (data) {
                if(data.status == 'success') {
                    update_summary();
                    $('#plan_price'+id).text(plan_price);
                    if(data.estimated_delivered_days_msg != ''){
                        $('#excepted_delivery'+id).text(data.estimated_delivered_days_msg);
                    }
                    if(data.service_plan != ''){
                        $('#plan_package_name'+id).text(data.service_plan);
                    }
                    if(data.service_plan_delivery_days != ''){
                        $('#plan_delivery'+id).text(data.service_plan_delivery_days);
                    }
                }
            }
        });
    });

    $('.extra_continue_checkout_btn').click(function(){
        toastr.error("Please choose atleast one payment method for proceed further.", "Error");
    });
});

function update_cart_quantity_clicks() {
    $('.plus').unbind('click').bind('click', function () {
        var id = $(this).data('cartid');
        //var quantity = parseInt($('#cart_quantity'+id).val());
        var quantity = parseInt($('#cart_quantity'+id).text());
        //$('#cart_quantity'+id).val(quantity + 1);
        $('#cart_quantity'+id).text(quantity + 1);
        update_cart_quantity(id,quantity + 1);
    });

    $('.minus').unbind('click').bind('click', function () {
        var id = $(this).data('cartid');
        //var quantity = parseInt($('#cart_quantity'+id).val());
        var quantity = parseInt($('#cart_quantity'+id).text());
        if (quantity > 1) {
            //$('#cart_quantity'+id).val(quantity - 1);
            $('#cart_quantity'+id).text(quantity - 1);
            update_cart_quantity(id,quantity - 1);
        }
    });
}

function load_div(id, callback=false) {
    $(id).load(window.location.href + ' '+id+' > div', function() {
        if(callback != false){
            callback();
        }
    });
}

function update_summary() {
    load_div('#sticky_block', function() {
        checkout_btn_click();
        discount_coupon_click();
        remove_coupon_btn_click();
    });
    /* $.ajax({
		type: "POST",
		url: $('#frm_cart_payment').attr('action'),
		data: $('#frm_cart_payment').serialize(),		
		success: function (result)
		{
			if (result.success == true) {
				$('.sticky-block').html(result.html);
			}
		}
	});
	return false; */
}

function update_cart_quantity(id,quantity) {
    $.ajax({
        type: "POST",
        url: update_cart_route,
        async:false,
        data: {
            "_token": _token,
            "id": id,
            "quantity": quantity
        },
        success: function (data) {
            if(data.status == 'success') {
                update_summary();
            }
        }
    });
}

function update_extra_cart_quantity(id,quantity,div_id) {
    $.ajax({
        type: "POST",
        url: update_extra_cart_route,
        data: {
            "_token": _token,
            "id": id,
            "quantity": quantity
        },
        success: function (data) {
            if(data.status == 'success') {
                load_div('#add_ons_div'+div_id, function() {
                    load_all_extra();
                });
                update_summary();
            }
        }
    });
}

function extra_remove_btn_click() {
    $('.extra_remove_btn').unbind('click').bind('click', function(){
        var cart_extra_id = $(this).data('cartextraid');
        var extra_id = $(this).data('extraid');
        $.ajax({
            type: "POST",
            url: remove_add_ons_route,
            data: {
                "_token": _token,
                "cart_extra_id": cart_extra_id,
            },
            success: function (data) {
                if(data.status == 'success') {
                    load_div('#add_ons_div'+extra_id, function() {
                        load_all_extra();
                    });
                    update_summary();
                }
            }
        });
    });
}

function extra_add_btn_click() {
    $('.extra_add_btn').unbind('click').bind('click', function(){
        var extra_id = $(this).data('extraid');
        var cart_id = $(this).data('cartid');
        var extra_qty_div = $('#extra_quantity_'+cart_id+'_'+extra_id);
        if(this.checked) {
            $('#bundle-service-text').hide();
            $.ajax({
                type: "POST",
                url: update_add_ons_route,
                async:false,
                data: {
                    "_token": _token,
                    "cart_id": cart_id,
                    "service_extra_id": extra_id,
                    "qty": parseInt(extra_qty_div.text())
                },
                success: function (data) {
                    //console.log(data);
                    if(data.status == 'success') {
                        load_div('#add_ons_div_'+cart_id+'_'+extra_id, function() {
                            load_all_extra();
                        });
                        update_summary();
                        $('#add_on_quantity_section_'+cart_id+'_'+extra_id).show();
                        if(data.total_cart_extra > 0) {
                            $('#extra_counter'+cart_id).show();
                            $('#extra_counter'+cart_id).text(data.total_cart_extra);
                        } else {
                            $('#extra_counter'+cart_id).hide();
                        }
                    }
                }
            });
        } else {
            var cart_extra_id = $(this).data('cartextraid');
            $.ajax({
                type: "POST",
                url: remove_add_ons_route,
                data: {
                    "_token": _token,
                    "cart_id": cart_id,
                    "cart_extra_id": cart_extra_id,
                },
                success: function (data) {
                    if(data.status == 'success') {
                        load_div('#add_ons_div_'+cart_id+'_'+extra_id, function() {
                            load_all_extra();
                        });
                        update_summary();
                        $('#add_on_quantity_section_'+cart_id+'_'+extra_id).show();
                        if(data.total_cart_extra > 0) {
                            $('#extra_counter'+cart_id).show();
                            $('#extra_counter'+cart_id).text(data.total_cart_extra);
                        } else {
                            $('#extra_counter'+cart_id).hide();
                        }
                    }
                }
            });
        }
    });
}

function extra_plus_click() {
    $('.extra-plus').unbind('click').bind('click', function() {
        var cartextraid = $(this).data('cartextraid');
        var cartid = $(this).data('cartid');
        var extra_id = $(this).data('extraid');
        var extra_qty_div = $('#extra_quantity_'+cartid+'_'+extra_id);
        
        var quantity = parseInt(extra_qty_div.text());
        extra_qty_div.text(quantity + 1);
        if(cartextraid !== undefined && cartextraid > 0 && cartextraid != '') {
            update_extra_cart_quantity(cartextraid,quantity + 1, extra_id,cartid);
        }
    });
}

function extra_minus_click() {
    $('.extra-minus').unbind('click').bind('click', function() {
        var cartextraid = $(this).data('cartextraid');
        var cartid = $(this).data('cartid');
        var extra_id = $(this).data('extraid');
        var extra_qty_div = $('#extra_quantity_'+cartid+'_'+extra_id);
        var quantity = parseInt(extra_qty_div.text());
        if (quantity > 1) {
            extra_qty_div.text(quantity - 1);
            if(cartextraid !== undefined && cartextraid > 0 && cartextraid != '') {
                update_extra_cart_quantity(cartextraid,quantity - 1, extra_id,cartid);
            }
        }
    });
}

function discount_coupon_click() {
    $('.apply_coupon_btn').on('click',function(){
        var couponCode = $('#coupan_code_new').val();
        $.ajax({
            url : apply_coupen_route,
            type : 'post',
            data : {"_token": _token,'coupon_code':couponCode},
            success : function(data){
                if(data.status == 'success')
                {
                    update_summary();
                    toastr.success(data.message, "Success");
                }
                else
                {
                    update_summary();
                    toastr.error(data.message, "Error");
                }
            }
        });
    });
    /* $('.discountCoupon').on('click',function(){
        var id = $(this).data('id');
        var cartid = $(this).data('cartid');
        var couponCode = $(this).parent().find('.couponCodeNew').val();
        $.ajax({
            url : apply_coupen_route,
            type : 'post',
            data : {"_token": _token,'id':id,'couponCode':couponCode},
            success : function(data){
                if(data.success == true)
                {
                    $('.couponMessage'+id).html("<p style='color:"+data.color+"'>"+data.message+"</p>");
                    load_div('#cart_details_div'+cartid, function() {
                        discount_coupon_click();
                        load_all_extra();
                        remove_cart_click();
                        update_cart_quantity_clicks();
                    });
                    update_summary();
                }
                else
                {
                    $('.couponMessage'+id).html("<p style='color:"+data.color+"'>"+data.message+"</p>");
                }
            }
        });
    }); */
}

function load_all_extra() {
    extra_plus_click();
    extra_minus_click();
    extra_add_btn_click();
    extra_remove_btn_click();
}

function show_summary_div() {
    $('.summary_div').show();
    $('.checkout_div').hide();
}

function remove_cart_click() {
    $('.remove_cart_link').unbind('click').bind('click', function () {
		var id =$(this).data('id');
		$.ajax({
			type: "POST",
			url: $(this).data('url'),
			dataType: 'json',
			cache: false,
			data:  { _token: _token, id : id},
			success: function(data) {
				if(data.success == true){
                    alert_success("Item removed from your cart.");
                    load_div('#main_div_id', function() {
                        update_cart_quantity_clicks();
                        load_all_extra();
                        discount_coupon_click();
                        remove_cart_click();
                        reinitialize_slider();
                    });
                    update_summary();
				} else {
					alert_error("Something goes wrong.");
				}
			}
		});
		return false;
    });
}

function checkout_btn_click() {
    $('.checkout_btn').unbind('click').bind('click', function() {
        if(cart_size > 0) {
            $('.checkout_div').show();
            $('.summary_div').hide();
            if($("#wallet_check").prop("checked") == true){
                $('#is_from_wallet').val(1);
                $('#allow_wallet').val(1);
                $('#payFromWalletForm').css('display','block');
                $('#payCreditCardForm').css('display','none');
            }
            if($("#promotional_check").prop("checked") == true){
                $('#is_from_promotional').val(1);
                $('#allow_promotional').val(1);
                $('#payFromWalletForm').css('display','block');
                $('#payCreditCardForm').css('display','none');
            }
            if($("#creditcard_check").prop("checked") == true){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','block');
            }
            if($("#wallet_check").prop("checked") != true && $("#promotional_check").prop("checked") != true && $("#creditcard_check").prop("checked") != true) {
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('.extra_continue_checkout_btn').show();
            }
            checkboxes_click();
        } else {
            //toastr.success(data.message, "Success");
            toastr.error("Your cart is empty, Please add something to continue.", "Error");
            return true;
        }
    });
}

function checkboxes_click() {
    $("#wallet_check").change(function() {
        $('.extra_continue_checkout_btn').hide();
        $('#is_from_wallet').val(0);
        $('#allow_wallet').val(0);
        $('#payFromWalletForm').removeClass('disable-class');
        $('#paypalForm').removeClass('disable-class');
        var pro_amount = $(this).data('promotional');
        var wallet_amount = $(this).data('wallet');
        var bill_amount = $(this).data('bill');
        if(this.checked) {
            /* if(pro_amount == bill_amount && bill_amount == wallet_amount) {
                $("#promotional_check").prop("checked", false);
            } */
            if($("#promotional_check").prop("checked") == true) {
                wallet_amount = wallet_amount + pro_amount;
            }
            $("#creditcard_check").prop("checked", false);
            $('#is_from_wallet').val(1);
            $('#allow_wallet').val(1);
            $('#payCreditCardForm').css('display','none');
            $('#payFromWalletForm').css('display','block');
            if(wallet_amount == bill_amount) {
                $('#paypalForm').addClass('disable-class');
            } else if(wallet_amount == 0) {
                $('#payFromWalletForm').addClass('disable-class');
            } else if(wallet_amount < bill_amount) {
                $('#payFromWalletForm').addClass('disable-class');
            } else {
                $('#paypalForm').css('display','block');
            }
        } else {
            $('#paypalForm').css('display','block');
            if($("#promotional_check").prop("checked") == true) {
                $("#creditcard_check").prop("checked", false);
                $('#payCreditCardForm').css('display','none');
                $('#payFromWalletForm').css('display','block');
                if(pro_amount == bill_amount) {
                    $('#paypalForm').addClass('disable-class');
                } else if(pro_amount == 0) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else if(pro_amount < bill_amount) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else {
                    $('#paypalForm').css('display','block');
                }
            } else 
            if($("#creditcard_check").prop("checked") == true){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','block');
            } else {
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('.extra_continue_checkout_btn').show();
            }
        }
    });

    $("#promotional_check").change(function() {
        $('.extra_continue_checkout_btn').hide();
        $('#is_from_promotional').val(0);
        $('#allow_promotional').val(0);
        $('#payFromWalletForm').removeClass('disable-class');
        $('#paypalForm').removeClass('disable-class');
        var wallet_amount = $(this).data('wallet');
        var pro_amount = $(this).data('promotional');
        var bill_amount = $(this).data('bill');
        if(this.checked) {
            if(pro_amount == bill_amount) {
                $("#wallet_check").prop("checked", false);
                $("#wallet_check").parent().addClass('disable-class');
                $("#wallet_check").attr('disabled',true);
            }
            if($("#wallet_check").prop("checked") == true) {
                pro_amount = pro_amount + wallet_amount;
            }
            $("#creditcard_check").prop("checked", false);
            $('#is_from_promotional').val(1);
            $('#allow_promotional').val(1);
            $('#payCreditCardForm').css('display','none');
            $('#payFromWalletForm').css('display','block');
            if(pro_amount == bill_amount) {
                $('#paypalForm').addClass('disable-class');
            } else if(pro_amount == 0) {
                $('#payFromWalletForm').addClass('disable-class');
            } else if(pro_amount < bill_amount) {
                $('#payFromWalletForm').addClass('disable-class');
            } else {
                $('#paypalForm').css('display','block');
            }
        } else {
            $('#paypalForm').css('display','block');
            if($("#wallet_check").prop("checked") == true) {
                $("#creditcard_check").prop("checked", false);
                $('#payCreditCardForm').css('display','none');
                $('#payFromWalletForm').css('display','block');
                if(wallet_amount == bill_amount) {
                    $('#paypalForm').addClass('disable-class');
                } else if(wallet_amount == 0) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else if(wallet_amount < bill_amount) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else {
                    $('#paypalForm').css('display','block');
                }
            } else if($("#creditcard_check").prop("checked") == true){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','block');
            } else {
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('.extra_continue_checkout_btn').show();
            }
        }
    });

    $("#creditcard_check").change(function() {
        $('.extra_continue_checkout_btn').hide();
        $('#is_from_wallet').val(0);
        $('#payFromWalletForm').removeClass('disable-class');
        $('#paypalForm').removeClass('disable-class');
        $('#paypalForm').css('display','block');
        if(this.checked) {
            $("#wallet_check").prop("checked", false);
            $("#promotional_check").prop("checked", false);
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','block');
        } else {
            if($("#wallet_check").prop("checked") == true){
                $('#payFromWalletForm').css('display','block');
                $('#payCreditCardForm').css('display','none');
            } else {
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('.extra_continue_checkout_btn').show();
            }
        }
    });
}

function reinitialize_slider() {
    $(".popular-grid-cart-new").slick({
        // dots: true,
        speed: 300,
        slidesToShow: 3,
        infinite:false,
        centerPadding: 30,
        prevArrow: '<span class="cus-arrow-left"><img src="'+back_slider_arrow+'"></span>',
        nextArrow: '<span class="cus-arrow-right"><img src="'+back_slider_arrow+'"></span>',
        responsive: [{
            breakpoint: 1024,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
            }
        },
        {
            breakpoint: 767,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }]
    });
}

function remove_coupon_btn_click() {
    $('.remove_coupon_btn').unbind('click').bind('click', function () {
        var url =$(this).data('url');
		$.ajax({
			type: "GET",
			url: url,
			dataType: 'json',
			success: function(data) {
				if(data.status == 'success'){
                    alert_success(data.message);
                    update_summary();
				} else {
					alert_error(data.message);
				}
			}
		});
		return false;
    });

    $('.remove_reorder_promo_btn').unbind('click').bind('click', function () {
        var url =$(this).data('url');
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(data) {
                if(data.status == 'success'){
                    alert_success(data.message);
                    update_summary();
                } else {
                    alert_error(data.message);
                }
            }
        });
        return false;
    });

    $('.clear-all-cart').unbind('click').bind('click', function() {
        var url = $(this).data("url");
        bootbox.confirm({
            message: 'Are you sure you want to remove all items from your cart?',
            buttons: {
                confirm: {
                    label: 'Empty My Cart',
                    className: 'btn-default'
                },
                cancel: {
                    label: 'Nevermind',
                    className: 'btn-default'
                }
            },
            callback: function (result) {
                if(result == true){
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: {"_token": _token},
                        success: function(data) {
                            if(data.status == true){
                                location.reload();
                            }else{
                                alert_error(data.message);
                                setTimeout(function(){
                                    location.reload();
                                },2000);
                            }
                        }
                    });
                }
            }
        });
    });
}