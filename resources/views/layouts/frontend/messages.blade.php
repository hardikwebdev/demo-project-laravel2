<?php
if(Session::has('errorFails')){?>
    <div class="alert alert-danger text-center mt-2 mx-auto py-3">
        <button class="close" data-close="alert"></button>
        <span> <?=Session::get('errorFails');?> </span>
    </div>
<?php }
if(Session::has('errorSuccess')){?>
    <div class="alert alert-success text-center mt-2 mx-auto py-3">
        <button class="close" data-close="alert"></button>
        <span> <?=Session::get('errorSuccess');?> </span>
    </div>
<?php }

if(Session::has('reactiveSuccess')){?>
 <div class="alert alert-success mt-2" >
     <div class="container">
        <button class="close" data-close="alert"></button><span><i class="fa fa-check-circle check-green"></i></span>
        <span class="ml-2"> <?=Session::get('reactiveSuccess');?> </span><br>
        <small class="ml-4"> Wishing you the very best of business! </small>
     </div>
 </div>
 <?php }
?>

@if (count($errors) > 0)
<div class="alert alert-danger mt-2">
    <!-- <strong>Whoops!</strong> There were some problems with your input.<br><br> -->
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif