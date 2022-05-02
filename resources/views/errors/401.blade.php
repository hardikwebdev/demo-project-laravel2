<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>demo</title>
    <link href="{{web_asset('css/custom-font-page-error.css')}}" rel="stylesheet">
    <link href="{{web_asset('css/custom-page-error.css')}}" rel="stylesheet">
</head>
<body>
    <div id="notfound">
        <div class="notfound">
            <div class="notfound-404">
                <h1>Oops!</h1>
                <h2>401 - Unauthorized Access!</h2>
            </div>
            @php
                $adminCheck=isset($admin) ? $admin : 0;
            @endphp
            @if($adminCheck == 1)
                <a href="{{url('/'.env('AdminBaseURL'))}}">Go TO Homepage</a>
            @else
                <a href="{{url('/')}}">Go TO Homepage</a>
            @endif
        </div>
    </div>
</body>
</html>