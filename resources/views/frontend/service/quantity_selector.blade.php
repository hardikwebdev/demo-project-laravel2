@if($show_quantity_section == true)
<div class="px-0">
    <div class="summary d-flex p-2 align-items-center justify-content-center">
        <span class="text-color-4 fa fa-minus font-8 px-2 cursor-pointer service-quantity-minus" data-id="{{$service_plan_id}}"></span>
        <span class="font-12 font-weight-bold text-color-6 px-2 service_quantity{{$service_plan_id}}">1</span>
        <span class="text-color-4 fa fa-plus font-8 px-2 cursor-pointer service-quantity-plus" data-id="{{$service_plan_id}}"></span>
    </div>
</div>
@else
    <input type="hidden" value="1" name="quantity" class="service_quantity_{{$service_plan_id}}">
@endif