@extends('layouts.frontend.main')
@section('pageTitle', $user->username.' | demo')
@section('content')

@include('frontend.seller.sellerheaderCustomOrderList')
{{-- <div class="content right">
    <br>
    @include('frontend.service.filterservicesCustom')
</div> --}}

<div class="clearfix"></div>
</div>


<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" align="left">Reviews</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>        
      </div>
      <div class="modal-body">
        <h5 id="orderName" align="center"></h5>
        <b>User: </b><p id="username"></p>

         <b>Ratings: </b><p id="rating"></p>
        
        <b>Review:</b><p id="review"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
@endsection
@section('css')
<link rel="stylesheet" href="{{front_asset('css/emoji/emoji.css')}}">
<link rel="stylesheet" type="text/json" href="{{front_asset('css/emoji/emoji.css.map')}}">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">

<style type="text/css">
.custom-order-status{
    border-top: 1px solid lightgray;
    margin-top: 10px;
    padding-top: 10px !important;
}
.getReview
{
    background: linear-gradient(90deg, #35abe9 , #08d6c1 );
}
.checked {
  color: gold;
}
.unchecked{
    color: #bfbfbf;   
}
</style>
@endsection
@section('scripts')
<script src="{{front_asset('js/emoji/config.js')}}"></script>
<script src="{{front_asset('js/emoji/util.js')}}"></script>
<script src="{{front_asset('js/emoji/jquery.emojiarea.js')}}"></script>
<script src="{{front_asset('js/emoji/emoji-picker.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function () {

      


        $(document).on('click','.getReview',function(){
            var review=$(this).data('review');
            var ordername=$(this).data('ordername');
            var rating=$(this).data('rating');
            var user=$(this).data('user');

            if(rating == 5)
            {
                $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span>');
            }
            else if(rating == 4)
            {
                $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span>');
            }
            else if(rating == 3)
            {
                $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
            }
            else if(rating == 2)
            {
                $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
            }
            else if(rating == 1)
            {
                 $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
            }
            else
            {
                $('#rating').html('<span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
            }

            $('#orderName').text(ordername);
            $('#username').text(user);
            $('#review').text(review);
            $('#myModal').modal('show');
        })



    //$('html,body').animate({scrollTop: $("#userprofile").offset().top - 150},'slow');

    var maxLength = 2500;

    /*Send a message*/
    $('.open-new-message').magnificPopup({
        type: 'inline',
        removalDelay: 300,
        mainClass: 'mfp-fade',
        closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
    });
    $('#message').keyup(function () {
        var length = $(this).val().length;
        $('#chars').text(length);
    });

    /*Create Custom Quote*/
    $('.open-custom-order').magnificPopup({
        type: 'inline',
        removalDelay: 300,
        mainClass: 'mfp-fade',
        closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
    });
    $('#descriptions').keyup(function () {
        var length = $(this).val().length;
        $('#chars_desc').text(length);
    });
    $('textarea').keyup(function() {
        var length = $(this).val().length;
        var length = maxLength-length;
        $('#chars_desc').text(length);
    });
});
</script>    
<script>
    $(function () {
        // Initializes and creates emoji set from sprite sheet
        window.emojiPicker = new EmojiPicker({
            emojiable_selector: '[data-emojiable=true]',
            assetsPath: "{{front_asset('img/emoji/')}}",
            popupButtonClasses: 'fa fa-smile-o'
        });
        // Finds all elements with `emojiable_selector` and converts them to rich emoji input fields
        // You may want to delay this step if you have dynamically created input fields that appear later in the loading process
        // It can be called as many times as necessary; previously converted input fields will not be converted again
        window.emojiPicker.discover();
    });
</script>
<script>
    // Google Analytics
    (function (i, s, o, g, r, a, m) {
        i['GoogleAnalyticsObject'] = r;
        i[r] = i[r] || function () {
            (i[r].q = i[r].q || []).push(arguments)
        }, i[r].l = 1 * new Date();
        a = s.createElement(o),
        m = s.getElementsByTagName(o)[0];
        a.async = 1;
        a.src = g;
        m.parentNode.insertBefore(a, m)
    })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

    ga('create', 'UA-49610253-3', 'auto');
    ga('send', 'pageview');
</script>
@endsection	