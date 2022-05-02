{{-- Share and Earn Start --}}
@php
$responce = $Service->get_affiliate_discount($Service);
$affiliate_base = 'promoteservice';
@endphp
@if (Auth::check() && $Service->user->is_delete == 0)
    <!-- Condition for premium user who enable affiliate link section need to display -->
    @if((App\User::checkPremiumUser($Service->user->id) == true && ($Service->is_affiliate_link==1)) || (App\User::checkPremiumUser($Service->user->id) == false))
        <div class="card mt-4 border p-4 course_bg-white">
            <h6>Share & Earn {{$responce['percentage']}}%  Cash Back!</h6>
            <div class="detail-point">
                <p class="text-oneline">Earn A {{$responce['percentage']}}% Commission By Sharing This Course</p>
                <p class="text-oneline">Here’s Your Course Affiliate Link</p>
                @if(Auth::user()->is_sub_user() == false)
                <div class="form-group affiliate-form gradient">
                    <input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
                    <button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}">Copy</button> 
                </div>
                
                @elseif($dataUser!=null)
                <div class="form-group affiliate-form gradient">
                    <input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
                    <button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}">Copy</button>
                </div>	
                @endif
            </div>
        </div>
    @endif
@elseif (Auth::check() && Auth::user()->username == 'culsons' && $Service->user->is_delete == 0)
<div class="card mt-4 border p-4 course_bg-white">
    <h6>Share & Earn {{$responce['percentage']}}%  Cash Back!</h6>
    <div class="detail-point">
        <p class="text-oneline">Earn A {{$responce['percentage']}}% Commission By Sharing This Course</p>
        <p class="text-oneline">Here’s Your Course Affiliate Link</p>
        @if(Auth::user()->is_sub_user() == false)
        <div class="form-group affiliate-form gradient">
            <input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
            <button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}">Copy</button> 
        </div>
        
        @elseif($dataUser!=null)
        <div class="form-group affiliate-form gradient">
            <input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
            <button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}">Copy</button>
        </div>	
        @endif
    </div>
</div>
@endif