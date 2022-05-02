$('document').ready(function(){
    //CKEDITOR.addCss('.cke_editable { background-color: black; color: white }');
    $('#accordionEx__2 .card .card-header a').first().parent().parent().addClass('active_card');
    //CKEDITOR.config.skin = 'moono-dark';

    $('#accordionEx__2 .card .card-header a').on('click', function(){
        $('#accordionEx__2 .card .card-header a').parent().parent().removeClass('active_card');
        if($(this).hasClass('collapsed')) {
            $(this).parent().parent().addClass('active_card');
        } else {
            $(this).parent().parent().removeClass('active_card');
        }
    });
});