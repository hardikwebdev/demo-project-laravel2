<div class="combo-btn-section">
    <h6>Combo Offer Discount</h6>
    <div class="detail-point">
        <p class="text-oneline">Also buy {{($otherService->count() == 1)?'this course':'these courses'}}</p>

        @foreach($otherService as $service)
        <a target="_blank" href="{{route('course_details',[$serviceUser->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
        @endforeach

        <p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$bundleService->getBuddleDiscount->discount}}</span> % discount</p>

        @if(Auth::check())
        {{ Form::open(['route' => ['cart_customize_combo'], 'method' => 'POST']) }}
        <input type="hidden" name="id" value="{{$Service->id}}">
        <input type="hidden" name="package" value="{{$cart_btn_plan->plan_type}}">
        <input type="hidden" name="bundle_id" value="{{$bundleService->bundle_id}}">
        <input type="hidden" name="utm_source" class="utm_source">
        <input type="hidden" name="utm_term" class="utm_term">
        <input type="hidden" name="plan_id" value="{{$cart_btn_plan->id}}">
        <input type="hidden" name="is_review_edition" value="0">
        <button class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2">Add Combo Course </button>
        {{ Form::close() }}
        @else
        <a href="#" class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2 cookie-cart-combo-save" data-id="{{$Service->id}}"  data-combo_plan_id="{{$cart_btn_plan->id}}" data-bundle_id="{{$bundleService->bundle_id}}" data-is_review_edition="0" data-packageType="{{$cart_btn_plan->plan_type}}">
            Add Combo Course
        </a>
        @endif
    </div>
</div>