<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
	<link rel="shortcut icon" href="{{url('public/frontend/assets/img/logo/favicon.png')}}">
	<title> demo | 404</title>
	<style>
		iframe {
			display: block;       
			background: #ffffff;
			border: none;   
			height: 100vh;
			width: 100vw;
		}
	</style>
</head>

<body style="margin:0px; padding:0px; background-color: #ffffff; height:100%; width:100%;">
	<iframe src="{{url('404')}}" width="100%" height="100%" frameborder="0"></iframe>
</body>
</html>
