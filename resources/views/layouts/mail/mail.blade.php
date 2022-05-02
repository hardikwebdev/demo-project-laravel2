@php
	$settings = App\EmailBanner::inRandomOrder()->first();
	$banner_url = "https://www.demo.com/public/frontend/assets/img/placements/AdvertiseWithdemo.gif";
	if(!is_null($settings) && !is_null($settings->banner_url)) {
		$banner_url = $settings->banner_url;
	}
	$banner_link = "https://advertise.demo.com";
	if(!is_null($settings) && !is_null($settings->banner_link)) {
		$banner_link = $settings->banner_link;
	}
@endphp
<!DOCTYPE html>



<html lang="en">
  <head>

	<meta charset="utf-8">
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   
	<title>@yield('title')</title>

    <style type="text/css">
        body{
            margin: 0px;
            padding: 0px;
        }

        *, *:before, *:after {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            -ms-box-sizing: border-box;
            -o-box-sizing: border-box;
            box-sizing: border-box;
        }
		.bo-t2{
			border-top:3px solid #e6e6e6;
		}
    </style>

  </head>
  <body style="background:#e6e6e6;padding-top:50px;margin: 0px;font-family: 'Inter', sans-serif;">
    
    <table style="padding:20px; font-family: 'Inter', sans-serif;width: 528px;margin: 0 auto;box-shadow: 0 0 29px 0 rgba(0,0,0,0.1);-webkit-box-shadow: 0 0 29px 0 rgba(0,0,0,0.1);-moz-box-shadow: 0 0 29px 0 rgba(0,0,0,0.1);-ms-box-shadow: 0 0 29px 0 rgba(0,0,0,0.1);-o-box-shadow: 0 0 29px 0 rgba(0,0,0,0.1);padding:20px; background:#fff;">
        <tr>
            <td style="width:100%;">
                <table style="width:100%;">
                    <tr>
                        <td  style="width:100%;text-align:center;">
                            <img src="https://www.demo.com/public/frontend/assets/img/logo/LogoHeader.png" alt="" style="max-width: 200px; padding-bottom:20px">
                        </td>
                    </tr>
					<tr>
						<td  style="width:100%;text-align:center;">
							<a href="{{$banner_link}}" target="_blank">
								<img src="{{$banner_url}}" alt="" style="max-width: 100%;padding-bottom:10px">
							</a>
                        </td>
					</tr>
                </table>
            </td>
		</tr>
		<tr>
            <td>
				@yield('content')
			</td>
		</tr>
		</table>
		<table  style="width: 528px;margin: 0 auto;">
        <tr>
            <td>
				<img src="https://www.demo.com/public/frontend/assets/img/logo/mail-footer-logo.png" style="max-width: 80px; margin-top:5px" alt="Logo" width="120">
			<td>
			<td>
				<p style="text-align:right; text-transform: uppercase; margin-top:0; margin-bottom:0;">
				<a style="color: #3e3e3e!important;font-weight: 600; font-size:10px; text-decoration:none;" href="https://www.demo.com">demo.com</a>
				@if(isset($receiver_secret))
				<a style="color: #3e3e3e!important;font-weight: 600; font-size:10px; text-decoration:none;" href="{{route('unsubscribe.email',encrypt($receiver_secret))}}"> | Unsubscribe</a>
				@endif
				<a style="color: #3e3e3e!important;font-weight: 600; font-size:10px; text-decoration:none;" href="https://www.demo.com/privacy"> | Privacy Policy</a>
				<a style="color: #3e3e3e!important;font-weight: 600; font-size:10px; text-decoration:none;" href="https://demo.freshdesk.com/"> | Contact Support</a>
				</p>
				<p  style="text-align:right; margin-top:0; margin-bottom:0;">
					<label   style="color: #3e3e3e!important;font-weight: 600; font-size:10px; text-decoration:none; line-height:1;">&copy; {{date('Y')}} demo Inc.</label>
				</p>
			</td>
			
        </tr>
    </table>
	@yield('javascripts')
</body>
</html>