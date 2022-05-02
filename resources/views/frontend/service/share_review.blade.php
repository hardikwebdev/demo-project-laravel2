<html>
<head>
    <link href="{{url('public/frontend/assets/vendor/fontawesome-free/css/all.css')}}" rel="stylesheet">
    <style>
        .main_blue_div {
            background-color: #4b5bee;
            height: auto;
            width: auto;
        }
        .first_div {
            /* padding: 35px 25px 10px 25px; */
            padding:00px 25px 25px 25px;
            font-size: 40px;
            text-align: center;
        }
        .second_div {
            padding: 5px 15px 30px; 
            text-align: center;
            position: relative;
            /* top: 40%;
            transform: translateY(-50%); */
        }
        .second_div span{
            font-weight: 500;
            color: white;
            font-size: 20px;
            text-transform: uppercase;
        }
        .third_div {
            background-color: #1661d8;
            padding: 5px;
            color: white;
            min-height: 70px;
            position: absolute;
            width: 100%;
            bottom: 0;
        }
        .star_checked {
            color: #ffa200;
        }
        .star_checked_export {
            color: #ffa200;
        }
        .star_unchecked {
            color: gray;
        }
        .m-l-22 {
            margin-left: 22%;
        }
        .p1 {
            font-size: 18px; 
            margin: 0px;
        }
        .p2 {
            font-size: 14px; 
            margin: 0px;
            margin-bottom: 2px;
        }
        .web_url {
            font-size: 11px; 
            margin: 0px;
        }
        .com_logo {
            height: 15px; 
            float: right;
            margin-top: 4px;
        }
        .profile_img {
            position: absolute; 
            top: -20px;
        }
        .custom_hr {
            border-top: 4px solid #ffa200;
            width: 35px;
            border-radius: 10px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .main_blue_div .fs-14{
            font-size:14px;
        }
        
    </style>
</head>
<body>
    <div class="main_blue_div">
        <!-- <div class="blue_bg"> -->
            <div class="second_div">
                @php
                $star_class = "star_checked";
                if(Auth::user()->web_dark_mode == 1) {
                    $star_class = "star_checked_export";
                }
                @endphp
                <div class="first_div">
                    <i class="fa fa-star @if($info['seller_rating']>=1) {{$star_class}} @else star_unchecked @endif"></i>
                    <i class="fa fa-star @if($info['seller_rating']>=2){{$star_class}} @else star_unchecked @endif"></i>
                    <i class="fa fa-star @if($info['seller_rating']>=3){{$star_class}} @else star_unchecked @endif"></i>
                    <i class="fa fa-star @if($info['seller_rating']>=4){{$star_class}} @else star_unchecked @endif"></i>
                    <i class="fa fa-star @if($info['seller_rating']>=5){{$star_class}} @else star_unchecked @endif"></i>
                </div>
                <span class="{{ ( strlen($info['review']) < 140 ) ? '' :'fs-14'}} description-info">{{display_content($info['review'], 180)}}</span>
                <br>
                <hr class="custom_hr">
                <span class="cus-buyer">{{$info['buyer']}}</span>
            </div>
        <!-- </div> -->
        <div class="third_div">
            <div class="m-l-130">
                <p class="p1">{{$info['seller']}}</p>
                <p class="p2">{{display_title($info['service'], 35)}}</p>
                <span class="web_url">Check out My Service On demo.com</span>
                <img class="com_logo" src="{{url('public/frontend/assets/img/demo-white-logo.png')}}">
            </div>
            <div class="profile_img">
                <img src="{{$info['buyer_image']}}" alt="profile-image" class="img-fluid headerProfile" height="115" width="115">
            </div>
        </div>
    </div>
</body>
</html>