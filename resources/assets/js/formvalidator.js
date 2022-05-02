
var restrict_domain_list = {};
if($('#createaccount').length > 0){
    function get_restrict_domain_list() {
        $.ajax({
            type: "POST",
            url: restrict_email_domain,
            dataType: "JSON",
            data: {
                "_token": _token,
            },
            success: function (data) {
               restrict_domain_list = data.domains.map(index => index.domain_name);
            }
        }); 
    }
}

if($('#create_order_tag').length > 0){
    $('#create_order_tag').bootstrapValidator({
        fields: {
            tag_name: {
                validators: {
                    notEmpty: {
                        message: 'Tag name is required.'
                    },
                    stringLength: {
                        max: 50,
                        message: 'The tag name must be less than 50 characters.'
                    },
                }
            },
        }
    }).on('error.validator.bv', function (e, data) {
    });
}