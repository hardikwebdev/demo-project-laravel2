$('document').ready(function(){
	
	select2_rent_spot_pick_service_bind_fn();
	
	$('.rent_spot_pick_service').on('change', function(){
		$('.rent_spot_service_card_preview').empty();
		var service_id = $('.rent_spot_pick_service').val();
		if(service_id == '' || service_id.length == 0) {
			$('.rent_spot_pick_service_error').show();
			return false;
		} else {
			$('.rent_spot_pick_service_error').hide();
		}
		$.ajax({
            url: get_service_card_preview,
            method: "POST",
            data: {'_token':_token , 'service_id' : service_id},
            dataType: "json",
            success: function(data){
                if(data.status == 'success') {
					$('.rent_spot_service_card_preview').append(data.html);
				}
            }
        });
	});

	$('#cancel_promote_service_modal_btn').on('click', function(){
		clear_rent_spot_service_form();
		$('#select_your_service_for_rent_spot_modal_id').modal('hide');
	});

	$('#promote_service_for_rent_spot_btn').on('click', function(){
		var plan_secret = $('#plan_secret').val();
		var service_id = $('.rent_spot_pick_service').val();
		if(service_id == '' || service_id.length == 0) {
			$('.rent_spot_pick_service_error').show();
			return false;
		} else {
			$('.rent_spot_pick_service_error').hide();
		}
		$('#rent_ad_spot_form_id').submit();
	});

	$('#select_your_service_for_rent_spot_modal_id').on('hidden.bs.modal', function () {
		clear_rent_spot_service_form();
	});

	$('#select_your_service_for_rent_spot_modal_id').on('shown.bs.modal', function () {
		select2_rent_spot_pick_service_bind_fn();
	});

    select_service_for_rent_spot_click_fn();
}); 

function clear_rent_spot_service_form() {
    if($('.rent_spot_pick_service_error').is(":visible")) {
	    $('.rent_spot_pick_service_error').hide();
    }
	$('#plan_secret').val();
    if($('.rent_spot_pick_service').val() != '') {
	    $(".rent_spot_pick_service").select2("val", "");
    }
	$('.rent_spot_service_card_preview').empty();
}

function select2_rent_spot_pick_service_bind_fn() {
	$(".rent_spot_pick_service").select2({
		placeholder: "Select your service here",
		//dropdownParent: $('#select_your_service_for_rent_spot_modal_id'),
		minimumInputLength: 1,
        ajax: { 
			url: get_my_service_list,
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
					results: response.services
				};
			},
			cache: true
		},
	});
}
function select_service_for_rent_spot_click_fn() {
	$('.select_service_for_rent_spot').on('click', function(){
        var plan = $(this).data('plan');
        $('#plan_secret').val(plan);
        $('.rent_spot_pick_service_error').hide();
        $('#select_your_service_for_rent_spot_modal_id').modal('show');
    });
}