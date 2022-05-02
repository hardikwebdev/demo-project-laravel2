<form method="GET" action="{{route('services_view')}}" class="pt-5">
    <div class="form-row search-form">
        <div class="col-md-12 col-lg-12 col-xl-12">
            @php
            $search = "";
            $search_by = "";
            $service_id = "";
            if (isset($_GET)) {
                if (isset($_GET['q'])) {
                    $search = $_GET['q'];
                }
                if (isset($_GET['search_by'])) {
                    $search_by = $_GET['search_by'];
                }
                if (isset($_GET['service_id'])) {
                    $service_id = $_GET['service_id'];
                }
            }
            @endphp
            <div class="input-group home-page-search">
                
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-block homepage-search-btn"><i class="fa fa-search" aria-hidden="true"></i></button>
                </span>
                <input type="text" class="form-control searchtext ui-autocomplete-input" name="q" id="common_search_home" autocomplete="off" placeholder="Search..." value="{{$search}}">

                <span class="input-group-btn">
                    <input type="hidden" name="search_by" class="hid_search_by" value="{{($search_by)?$search_by:'Services'}}" id="search_by_home">

                    <input type="hidden" name="service_id" value="{{$service_id}}" id="hid_home_service_id">

                    <button type="button" class="search-by btn-default dropdown-toggle" data-toggle="dropdown">{{($search_by)?$search_by:'Services'}}</button>
                    <ul class="dropdown-menu pull-right">
                          <li data-value="Services"><a href="javascript:void(0);">Services</a></li>
                          <li data-value="Courses"><a href="javascript:void(0);">Courses</a></li>
                          <li data-value="Categories"><a href="javascript:void(0);">Categories</a></li>
                          <li data-value="Users"><a href="javascript:void(0);">Users</a></li>
                    </ul>
                </span>
            </div>
        </div>
    </div>
</form>