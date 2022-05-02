<div class="mt-4 d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-clock text-color-4"></i>
        <span class="text-color-4 font-14">Instant Access</span>
    </div>
    <div>
        <i class="fas fa-lock course_text-dark-green"></i>
        <span class="course_text-dark-green font-14">Secure Transaction</span>
    </div>
</div>
@php 
$show_button = true;
use App\User;
$userObj = new User;
$is_course_training_account = $userObj->is_course_training_account($Service);
@endphp
@if (Auth::check())
    @if($cart_btn_plan->type="lifetime_plan" && !empty($purchaseMonthlyDetails) && $purchaseMonthlyDetails->status != "cancelled") <!-- Begin : Check user have already purchased this course -->
        
        @if($purchaseMonthlyDetails->subscription->is_cancel == 0 && $purchaseMonthlyDetails->subscription->is_payment_received == 1 && ($purchaseMonthlyDetails->is_dispute == 0 || $purchaseMonthlyDetails->is_dispute == 1 && $purchaseMonthlyDetails->dispute_favour != 0) )
        <div class="mt-3 text-center font-16"><i class="fas fa-info-circle text-color-1 "></i> <em>You monthly subscription will be cancelled if you purchase lifetime access</em></div>
        
        @else
            @php $show_button = false; @endphp
        @endif

      
    @endif
    @if($show_button == true)
    {{ Form::open(['route' => ['cart_customize'], 'method' => 'POST']) }}
    <input type="hidden" name="id" value="{{$Service->id}}">
    <input type="hidden" name="plan_id" value="{{$cart_btn_plan->id}}">
    <input type="hidden" name="influencer" value="{{$influencer}}">
    <input type="hidden" name="is_review_edition" value="0">

    <input type="hidden" name="utm_source" class="utm_source">
    <input type="hidden" name="utm_term" class="utm_term">
    <button class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2">{{($is_course_training_account == true)?'Enroll Free':'Add to Cart'}}</button>
    {{-- BEGIN - Quick checkout --}}
    {{-- <button name="direct_checkout" value="1" class="btn btn-block bg-primary-blue mt-3 py-2 quick-checkout-btn">Quick Checkout</button> --}}
    {{-- END - Quick checkout --}}
    {{ Form::close() }}
    @endif

@else
    @if($is_course_training_account == true)
    <a href="{{url('login')}}" class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2">
        Enroll Free
    </a>
    @else
    <a href="#" class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2 cookie-cart-save" data-service_id="{{$Service->id}}" data-plan_id="{{$cart_btn_plan->id}}" data-influencer="{{$influencer}}" data-is_review_edition="0">
        Add to Cart
    </a>
    @endif
@endif
