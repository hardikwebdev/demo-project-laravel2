$(document).ready(function () {
    $('.checkout_div').hide();
    load_all_extra();
    discount_coupon_click();
    remove_cart_click();
    checkout_btn_click();
    update_cart_quantity_clicks();
    remove_coupon_btn_click();
    add_another_promo_link_click();
    use_wallet_with_paypal_click();
    use_wallet_with_skrill_click();
    use_promo_with_paypal_click();
    use_promo_with_skrill_click();

    if($('#use_wallet_with_paypal').prop('checked')) {
        $('#is_from_wallet').val(1);
        if($('#use_wallet_with_paypal_div').hasClass('alert-secondary')) {
            $('#use_wallet_with_paypal_div').removeClass('alert-secondary');
        }
        if(!$('#use_wallet_with_paypal_div').hasClass('alert-primary')) {
            $('#use_wallet_with_paypal_div').addClass('alert-primary');
        }
        if($('#use_wallet_with_paypal_text').hasClass('text-color-4')) {
            $('#use_wallet_with_paypal_text').removeClass('text-color-4');
        }
        if(!$('#use_wallet_with_paypal_text').hasClass('text-color-1')) {
            $('#use_wallet_with_paypal_text').addClass('text-color-1');
        }
    } else {
        $('#is_from_wallet').val(0);
        if($('#use_wallet_with_paypal_div').hasClass('alert-primary')) {
            $('#use_wallet_with_paypal_div').removeClass('alert-primary');
        }
        if(!$('#use_wallet_with_paypal_div').hasClass('alert-secondary')) {
            $('#use_wallet_with_paypal_div').addClass('alert-secondary');
        }
        if($('#use_wallet_with_paypal_text').hasClass('text-color-1')) {
            $('#use_wallet_with_paypal_text').removeClass('text-color-1');
        }
        if(!$('#use_wallet_with_paypal_text').hasClass('text-color-4')) {
            $('#use_wallet_with_paypal_text').addClass('text-color-4');
        }
    }

    if($('#use_wallet_with_skrill').prop('checked')) {
        $('#allow_from_wallet').val(1);
        if($('#use_wallet_with_skrill_div').hasClass('alert-secondary')) {
            $('#use_wallet_with_skrill_div').removeClass('alert-secondary');
        }
        if(!$('#use_wallet_with_skrill_div').hasClass('alert-primary')) {
            $('#use_wallet_with_skrill_div').addClass('alert-primary');
        }
        if($('#use_wallet_with_skrill_text').hasClass('text-color-4')) {
            $('#use_wallet_with_skrill_text').removeClass('text-color-4');
        }
        if(!$('#use_wallet_with_skrill_text').hasClass('text-color-1')) {
            $('#use_wallet_with_skrill_text').addClass('text-color-1');
        }
    } else {
        $('#allow_from_wallet').val(0);
        if($('#use_wallet_with_skrill_div').hasClass('alert-primary')) {
            $('#use_wallet_with_skrill_div').removeClass('alert-primary');
        }
        if(!$('#use_wallet_with_skrill_div').hasClass('alert-secondary')) {
            $('#use_wallet_with_skrill_div').addClass('alert-secondary');
        }
        if($('#use_wallet_with_skrill_text').hasClass('text-color-1')) {
            $('#use_wallet_with_skrill_text').removeClass('text-color-1');
        }
        if(!$('#use_wallet_with_skrill_text').hasClass('text-color-4')) {
            $('#use_wallet_with_skrill_text').addClass('text-color-4');
        }
    }
    

    $('.package_type').unbind('change').bind('change', function() {
        $('#bundle-service-text').hide();
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
                    $('#bundle-service-text').show();
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
        $('#bundle-service-text').hide();
        //$('#cart_quantity'+id).val(quantity + 1);
        $('#cart_quantity'+id).text(quantity + 1);
        update_cart_quantity(id,quantity + 1);
    });

    $('.minus').unbind('click').bind('click', function () {
        var id = $(this).data('cartid');
        //var quantity = parseInt($('#cart_quantity'+id).val());
        var quantity = parseInt($('#cart_quantity'+id).text());
        if (quantity > 1) {
            $('#bundle-service-text').hide();
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
        $('#bundle-service-text').show();
        //bundle_cart_validate();
        add_another_promo_link_click();
    });
}

var update_cart_ajax;
function update_cart_quantity(id,quantity) {
    update_cart_ajax = $.ajax({
        type: "POST",
        url: update_cart_route,
       // async:false,
        data: {
            "_token": _token,
            "id": id,
            "quantity": quantity
        },
        success: function (data) {
            if(data.status == 'success') {
                update_summary();
            } else {
                $('#cart_quantity'+id).text(data.quantity);
                toastr.error(data.message, "Error");
            }
        },
        beforeSend : function(){           
            if(update_cart_ajax != null) {
                update_cart_ajax.abort();
            }
        },
    });
}

var update_extra_cart_ajax;
function update_extra_cart_quantity(id,quantity,extra_id,cartid) {
    update_extra_cart_ajax = $.ajax({
        type: "POST",
        url: update_extra_cart_route,
        data: {
            "_token": _token,
            "id": id,
            "quantity": quantity
        },
        success: function (data) {
            if(data.status == 'success') {
                load_div('#add_ons_div_'+cartid+'_'+extra_id, function() {
                    load_all_extra();
                });
                update_summary();
            }
        },
        beforeSend : function(){           
            if(update_extra_cart_ajax != null) {
                update_extra_cart_ajax.abort();
            }
        },
    });
}


function extra_remove_btn_click() { //Function not used
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
var extra_add_btn_ajax;
function extra_add_btn_click() {
    $('.extra_add_btn').unbind('click').bind('click', function(){
        var extra_id = $(this).data('extraid');
        var cart_id = $(this).data('cartid');
        var extra_price = $(this).data('extra_price');
        var extra_qty_div = $('#extra_quantity_'+cart_id+'_'+extra_id);
        if(this.checked) {
            $('#bundle-service-text').hide();
            extra_add_btn_ajax = $.ajax({
                type: "POST",
                url: update_add_ons_route,
                async:false,
                data: {
                    "_token": _token,
                    "cart_id": cart_id,
                    "service_extra_id": extra_id,
                    "extra_price":extra_price,
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
                    } else {
                        load_div('#add_ons_div_'+cart_id+'_'+extra_id, function() {
                            load_all_extra();
                        });
                        toastr.error(data.message, "Error");
                    }
                },
                beforeSend : function(){           
                    if(extra_add_btn_ajax != null) {
                        extra_add_btn_ajax.abort();
                    }
                },
            });
        } else {
            var cart_extra_id = $(this).data('cartextraid');
            extra_add_btn_ajax = $.ajax({
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
                },
                beforeSend : function(){           
                    if(extra_add_btn_ajax != null) {
                        extra_add_btn_ajax.abort();
                    }
                },
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
            $('#bundle-service-text').hide();
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
                $('#bundle-service-text').hide();
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
                    if($('#coupan_code_new').hasClass('invalid-promocode')) {
                        $('#coupan_code_new').removeClass('invalid-promocode');
                    }
                    $('#apply_promo_code_error').text('');
                    toastr.success(data.message, "Success");
                }
                else
                {
                    /* update_summary();
                    toastr.error(data.message, "Error"); */
                    $('.apply_coupon_btn_div').hide();
                    $('#coupan_code_new').addClass('invalid-promocode');
                    $('#apply_promo_code_error').text(data.message);
                }
            }
        });
    });
    $('#coupan_code_new').unbind('keyup').bind('keyup', function() {
        if($('#coupan_code_new').hasClass('invalid-promocode')) {
            $('.apply_coupon_btn_div').show();
            if($('#coupan_code_new').hasClass('invalid-promocode')) {
                $('#coupan_code_new').removeClass('invalid-promocode');
            }
            $('#apply_promo_code_error').text('');
        }
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
    //extra_remove_btn_click();
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
                        //add_on_dropdown_click();
                    });
                    load_div('#cart_header_div', function() {});
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
    if(cart_size > 0) {
        var active_radio = $("input[name='radio_payment']:checked").val();
        if(active_radio == 'wallet'){
            $('#payFromWalletForm').css('display','block');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','none');
            hide_payment_processing_fee();
        }
        if(active_radio == 'paypal'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','block');
            show_payment_processing_fee(active_radio);
        }
        if(active_radio == 'creditcard'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','block');
            $('#paypalForm').css('display','none');
            show_payment_processing_fee(active_radio);
        }
        if(active_radio == 'skrill'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','none');
            $('#skrillForm').css('display','block');
            show_payment_processing_fee(active_radio);
        }
        checkboxes_click();
    } else {
        //toastr.success(data.message, "Success");
        //toastr.error("Your cart is empty, Please add something to continue.", "Error");
        return true;
    }
}

function checkboxes_click() {
    $(".radio_class").unbind('click').bind('click', function() {
        var active_radio = $("input[name='radio_payment']:checked").val();
        if(active_radio == 'wallet'){
            $('#payFromWalletForm').css('display','block');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','none');
            $('#skrillForm').css('display','none');
            $('.sub-wallet-desc').css('display','block');
            $('#use_wallet_with_paypal_div').attr("style", "display: none !important");
            $('#use_promo_with_paypal_div').attr("style", "display: none !important");
            $('#use_wallet_with_skrill_div').attr("style", "display: none !important");
            $('#use_promo_with_skrill_div').attr("style", "display: none !important");
            hide_payment_processing_fee();
        }
        if(active_radio == 'paypal'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','block');
            $('#skrillForm').css('display','none');
            $('.sub-wallet-desc').attr("style", "display: none !important");
            $('#use_wallet_with_skrill_div').attr("style", "display: none !important");
            $('#use_promo_with_skrill_div').attr("style", "display: none !important");
            $('#use_wallet_with_paypal_div').css('display','block');
            $('#use_promo_with_paypal_div').attr("style", "display: block");
            show_payment_processing_fee(active_radio);
        }
        if(active_radio == 'creditcard'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','block');
            $('#paypalForm').css('display','none');
            $('#skrillForm').css('display','none');
            $('.sub-wallet-desc').attr("style", "display: none !important");
            $('#use_wallet_with_paypal_div').attr("style", "display: none !important");
            $('#use_promo_with_paypal_div').attr("style", "display: none !important");
            $('#use_wallet_with_skrill_div').attr("style", "display: none !important");
            $('#use_promo_with_skrill_div').attr("style", "display: none !important");
            show_payment_processing_fee(active_radio);
        }
        if(active_radio == 'skrill'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','none');
            $('#skrillForm').css('display','block');
            $('.sub-wallet-desc').attr("style", "display: none !important");
            $('#use_wallet_with_paypal_div').attr("style", "display: none !important");
            $('#use_promo_with_paypal_div').attr("style", "display: none !important");
            $('#use_wallet_with_skrill_div').css('display','block');
            $('#use_promo_with_skrill_div').attr("style", "display: block");
            show_payment_processing_fee(active_radio);
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

function add_another_promo_link_click() {
    $('.add_another_promo_link').unbind('click').bind('click', function () {
        $('.apply_promo_input_section').show();
    });
}

function use_wallet_with_paypal_click() {
    $('#use_wallet_with_paypal').unbind('change').bind('change', function () {
        var walletandpromo = $(this).data('walletandpromo');
        var bill = $(this).data('bill');
        if(this.checked) {
            if(walletandpromo >= bill && $('#use_promo_with_paypal').is(':checked')) {
                $('#use_wallet_with_paypal').prop('checked', false);
                toastr.error("You have already enough balance to place the order.", "Error");
                return false;
            }
            $('#is_from_wallet').val(1);
            if($('#use_wallet_with_paypal_div').hasClass('alert-secondary')) {
                $('#use_wallet_with_paypal_div').removeClass('alert-secondary');
            }
            if(!$('#use_wallet_with_paypal_div').hasClass('alert-primary')) {
                $('#use_wallet_with_paypal_div').addClass('alert-primary');
            }
            if($('#use_wallet_with_paypal_text').hasClass('text-color-4')) {
                $('#use_wallet_with_paypal_text').removeClass('text-color-4');
            }
            if(!$('#use_wallet_with_paypal_text').hasClass('text-color-1')) {
                $('#use_wallet_with_paypal_text').addClass('text-color-1');
            }
        } else {
            $('#is_from_wallet').val(0);
            if($('#use_wallet_with_paypal_div').hasClass('alert-primary')) {
                $('#use_wallet_with_paypal_div').removeClass('alert-primary');
            }
            if(!$('#use_wallet_with_paypal_div').hasClass('alert-secondary')) {
                $('#use_wallet_with_paypal_div').addClass('alert-secondary');
            }
            if($('#use_wallet_with_paypal_text').hasClass('text-color-1')) {
                $('#use_wallet_with_paypal_text').removeClass('text-color-1');
            }
            if(!$('#use_wallet_with_paypal_text').hasClass('text-color-4')) {
                $('#use_wallet_with_paypal_text').addClass('text-color-4');
            }
        }
        show_payment_processing_fee('paypal');
    });
}

function use_wallet_with_skrill_click() {
    $('#use_wallet_with_skrill').unbind('change').bind('change', function () {
        var walletandpromo = $(this).data('walletandpromo');
        var bill = $(this).data('bill');
        if(this.checked) {
            if(walletandpromo >= bill && $('#use_promo_with_skrill').is(':checked')) {
                $('#use_wallet_with_skrill').prop('checked', false);
                toastr.error("You have already enough balance to place the order.", "Error");
                return false;
            }
            $('#allow_from_wallet').val(1);
            if($('#use_wallet_with_skrill_div').hasClass('alert-secondary')) {
                $('#use_wallet_with_skrill_div').removeClass('alert-secondary');
            }
            if(!$('#use_wallet_with_skrill_div').hasClass('alert-primary')) {
                $('#use_wallet_with_skrill_div').addClass('alert-primary');
            }
            if($('#use_wallet_with_skrill_text').hasClass('text-color-4')) {
                $('#use_wallet_with_skrill_text').removeClass('text-color-4');
            }
            if(!$('#use_wallet_with_skrill_text').hasClass('text-color-1')) {
                $('#use_wallet_with_skrill_text').addClass('text-color-1');
            }
        } else {
            $('#allow_from_wallet').val(0);
            if($('#use_wallet_with_skrill_div').hasClass('alert-primary')) {
                $('#use_wallet_with_skrill_div').removeClass('alert-primary');
            }
            if(!$('#use_wallet_with_skrill_div').hasClass('alert-secondary')) {
                $('#use_wallet_with_skrill_div').addClass('alert-secondary');
            }
            if($('#use_wallet_with_skrill_text').hasClass('text-color-1')) {
                $('#use_wallet_with_skrill_text').removeClass('text-color-1');
            }
            if(!$('#use_wallet_with_skrill_text').hasClass('text-color-4')) {
                $('#use_wallet_with_skrill_text').addClass('text-color-4');
            }
        }
        show_payment_processing_fee('skrill');
    });
}

function use_promo_with_paypal_click() {
    $('#use_promo_with_paypal').unbind('change').bind('change', function () {
        var walletandpromo = $(this).data('walletandpromo');
        var bill = $(this).data('bill');
        if(this.checked) {
            if(walletandpromo >= bill && $('#use_wallet_with_paypal').is(':checked')) {
                $('#use_promo_with_paypal').prop('checked', false);
                toastr.error("You have already enough balance to place the order.", "Error");
                return false;
            }
            $('#is_from_promotional').val(1);
            if($('#use_promo_with_paypal_div').hasClass('alert-secondary')) {
                $('#use_promo_with_paypal_div').removeClass('alert-secondary');
            }
            if(!$('#use_promo_with_paypal_div').hasClass('alert-primary')) {
                $('#use_promo_with_paypal_div').addClass('alert-primary');
            }
            if($('#use_promo_with_paypal_text').hasClass('text-color-4')) {
                $('#use_promo_with_paypal_text').removeClass('text-color-4');
            }
            if(!$('#use_promo_with_paypal_text').hasClass('text-color-1')) {
                $('#use_promo_with_paypal_text').addClass('text-color-1');
            }
        } else {
            $('#is_from_promotional').val(0);
            if($('#use_promo_with_paypal_div').hasClass('alert-primary')) {
                $('#use_promo_with_paypal_div').removeClass('alert-primary');
            }
            if(!$('#use_promo_with_paypal_div').hasClass('alert-secondary')) {
                $('#use_promo_with_paypal_div').addClass('alert-secondary');
            }
            if($('#use_promo_with_paypal_text').hasClass('text-color-1')) {
                $('#use_promo_with_paypal_text').removeClass('text-color-1');
            }
            if(!$('#use_promo_with_paypal_text').hasClass('text-color-4')) {
                $('#use_promo_with_paypal_text').addClass('text-color-4');
            }
        }
        show_payment_processing_fee('paypal');
    });
}

function use_promo_with_skrill_click() {
    $('#use_promo_with_skrill').unbind('change').bind('change', function () {
        var walletandpromo = $(this).data('walletandpromo');
        var bill = $(this).data('bill');
        if(this.checked) {
            if(walletandpromo >= bill && $('#use_wallet_with_skrill').is(':checked')) {
                $('#use_promo_with_skrill').prop('checked', false);
                toastr.error("You have already enough balance to place the order.", "Error");
                return false;
            }
            $('#allow_from_promotional').val(1);
            if($('#use_promo_with_skrill_div').hasClass('alert-secondary')) {
                $('#use_promo_with_skrill_div').removeClass('alert-secondary');
            }
            if(!$('#use_promo_with_skrill_div').hasClass('alert-primary')) {
                $('#use_promo_with_skrill_div').addClass('alert-primary');
            }
            if($('#use_promo_with_skrill_text').hasClass('text-color-4')) {
                $('#use_promo_with_skrill_text').removeClass('text-color-4');
            }
            if(!$('#use_promo_with_skrill_text').hasClass('text-color-1')) {
                $('#use_promo_with_skrill_text').addClass('text-color-1');
            }
        } else {
            $('#allow_from_promotional').val(0);
            if($('#use_promo_with_skrill_div').hasClass('alert-primary')) {
                $('#use_promo_with_skrill_div').removeClass('alert-primary');
            }
            if(!$('#use_promo_with_skrill_div').hasClass('alert-secondary')) {
                $('#use_promo_with_skrill_div').addClass('alert-secondary');
            }
            if($('#use_promo_with_skrill_text').hasClass('text-color-1')) {
                $('#use_promo_with_skrill_text').removeClass('text-color-1');
            }
            if(!$('#use_promo_with_skrill_text').hasClass('text-color-4')) {
                $('#use_promo_with_skrill_text').addClass('text-color-4');
            }
        }
        show_payment_processing_fee('skrill');
    });
}

function show_payment_processing_fee(from) {
    var payment_fee = 0;
    var pay = final_bill;
    if(from == 'paypal') {
        if($('#use_wallet_with_paypal').prop('checked')) {
            pay = pay - wallet_amt;
        }
        if($('#use_promo_with_paypal').prop('checked')) {
            pay = pay - promotional_amt;
        }
    }else if(from == 'skrill'){
        if($('#use_wallet_with_skrill').prop('checked')) {
            pay = pay - wallet_amt;
        }
        if($('#use_promo_with_skrill').prop('checked')) {
            pay = pay - promotional_amt;
        }
    }
    payment_fee = calculate_processing_fee(pay);
    
    $('#payment_processing_fee').text(payment_fee);
    $('#payment_processing_fee_div').css('display','block');
    var total_payable_amount = parseFloat(payment_fee)+parseFloat(final_bill);
    $('#final_bill_amount').text('$'+total_payable_amount.toFixed(2));
}

function hide_payment_processing_fee() {
    $('#final_bill_amount').text($('#base_total').val());
    $('#payment_processing_fee').text('');
    $('#payment_processing_fee_div').attr("style", "display: none !important");
}