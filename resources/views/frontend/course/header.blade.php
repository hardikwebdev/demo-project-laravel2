@php
$courseId='null';$current_step=0;
if(isset($Course)){
	$current_step=$Course->current_step;
	$courseId=$Course->id;
	$courseSEO=$Course->seo_url;
}
$currentPage = \Request::route()->getName();
@endphp
<header class="masthead text-white"> 
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4">
        <div class="row py-2">
			<div class="col-12 col-md-4">
				<p class="mb-0 font-24 font-weight-bold mt-3 mt-md-0">
					<a href="{{route('mycourses')}}" class="text-white">
						<i class="fas fa-chevron-left px-2" aria-hidden="true"></i> Back Courses
					</a>
				</p>
            </div>
            <div class="col-12 col-md-4 text-md-center">
                <p class="mb-0 text-white font-24 font-weight-bold mt-3 mt-md-0">
                    @if(isset($Course) && isset($Course->id) && $current_step>=5)
                    	Update Course
                    @else
                    	Create New Course
                    @endif
                </p>
            </div>
        </div>
	</div>
    </div>
</header>
<section class="popular-tab-icon create-new-service-menu">
	<div class="container">
		<div class="row">
			<div class="popular-tab-item">
				<ul class="nav nav-tabs" id="myTab" role="tablist">
					<li class="nav-item">
						<a class="nav-link @if($currentPage=='course.overview' || $currentPage=='course.update_overview'){{'active'}} @endif"  @if($courseId == 'null') href="{{route('course.overview')}}" @else href="{{route('course.update_overview',$courseSEO)}}" @endif>
							Overview
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link @if($currentPage=='course.description'){{'active'}}@endif"  @if($courseId == 'null' || $current_step<1)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('course.description',$courseSEO)}}" @endif>Description</a>
					</li>
					<li class="nav-item">
						<a class="nav-link @if($currentPage=='course.requirement'){{'active'}}@endif"  @if($courseId == 'null' || $current_step<2)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('course.requirement',$courseSEO)}}" @endif>Requirements</a>
					</li>
					<li class="nav-item">
						<a class="nav-link  @if($currentPage=='course.section') active @endif" @if($courseId == 'null' || $current_step<3) href="javascript:void(0);" class="tab-disabled" @else href="{{route('course.section', $courseSEO) }}" @endif>Course Section</a>
					</li>
					@if($current_step<=5)
					<li class="nav-item">
						<a class="nav-link @if($currentPage=='course.publish'){{'active'}}@endif"  @if($courseId == 'null' || $current_step<5)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('course.publish',$courseSEO)}}" @endif>Publish</a>
					</li>
					@endif
				</ul>
			</div>          
		</div>
	</div>
	
</section>