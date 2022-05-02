@if(@$imglink)
<div class="cus-service-job-banner cus-grid-full mb-2 text-center">
    <a href="{{($bannerlink) ? $bannerlink : '#' }}" class="job-banner-link">
        <img class="img-fluid" src="{{$imglink}}" />
    </a>
</div>
@endif