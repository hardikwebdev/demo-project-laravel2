<div class="product-showcase">
    <!-- PRODUCT LIST -->
    <div class="product-list grid column3-4-wrap">

        @foreach($Service as $row)
        <!-- PRODUCT ITEM -->
        <div class="product-item column">
            <!-- PRODUCT PREVIEW ACTIONS -->
            <div class="product-preview-actions">
                <!-- PRODUCT PREVIEW IMAGE -->
                <figure class="product-preview-image">
                    @if(isset($row->images[0]))
                    <img src="{{url('public/services/images/'.$row->images[0]->media_url)}}" alt="product-image">
                    @endif
                </figure>
                <!-- /PRODUCT PREVIEW IMAGE -->

                <!-- PREVIEW ACTIONS -->
                <div class="preview-actions">
                    <!-- PREVIEW ACTION -->
                    <div class="preview-action">
                        <a href="{{route('services_details',[$row->user->username,$row->seo_url])}}">
                            <div class="circle tiny primary">
                                <span class="icon-tag"></span>
                            </div>
                        </a>
                        <a href="{{route('services_details',[$row->user->username,$row->seo_url])}}">
                            <p>View</p>
                        </a>
                    </div>
                    <!-- /PREVIEW ACTION -->

                    <!-- PREVIEW ACTION -->
                    <div class="preview-action">
                        <a href="#">
                            <div class="circle tiny secondary">
                                <span class="icon-heart"></span>
                            </div>
                        </a>
                        <a href="#">
                            <p>Favourites +</p>
                        </a>
                    </div>
                    <!-- /PREVIEW ACTION -->
                </div>
                <!-- /PREVIEW ACTIONS -->
            </div>
            <!-- /PRODUCT PREVIEW ACTIONS -->

            <!-- PRODUCT INFO -->
            <div class="product-info">
                <a href="{{route('services_details',[$row->user->username,$row->seo_url])}}">
                    <p class="text-header text-capitalize">{{$row->title}}</p>
                </a>
                <p class="product-description"><?= substr(strip_tags($row->descriptions), 0, 30); ?>...</p>
                <a href="#">
                    <p class="category secondary">{{$row->category->category_name}}</p>
                </a>
                <p class="price"><span>$</span>{{isset($row->basic_plans->price)?$row->basic_plans->price:'0.0'}}</p>
            </div>
            <!-- /PRODUCT INFO -->
            <hr class="line-separator">

            <!-- USER RATING -->
            <div class="user-rating">
                <a href="#">
                    <figure class="user-avatar small">
                        <img src="{{get_user_profile_image_url($row->user)}}" alt="profile-image">
                    </figure>
                </a>
                <a href="{{route('viewuserservices',[$row->user->username])}}">
                    <p class="text-header tiny">{{$row->user->username}}</p>
                </a>
                <ul class="rating tooltip" title="Seller's Reputation">
                    <li class="rating-item">
                        <!-- SVG STAR -->
                        <svg class="svg-star">
                        <use xlink:href="#svg-star"></use>
                        </svg>
                        <!-- /SVG STAR -->
                    </li>
                    <li class="rating-item">
                        <!-- SVG STAR -->
                        <svg class="svg-star">
                        <use xlink:href="#svg-star"></use>
                        </svg>
                        <!-- /SVG STAR -->
                    </li>
                    <li class="rating-item">
                        <!-- SVG STAR -->
                        <svg class="svg-star">
                        <use xlink:href="#svg-star"></use>
                        </svg>
                        <!-- /SVG STAR -->
                    </li>
                    <li class="rating-item">
                        <!-- SVG STAR -->
                        <svg class="svg-star">
                        <use xlink:href="#svg-star"></use>
                        </svg>
                        <!-- /SVG STAR -->
                    </li>
                    <li class="rating-item empty">
                        <!-- SVG STAR -->
                        <svg class="svg-star">
                        <use xlink:href="#svg-star"></use>
                        </svg>
                        <!-- /SVG STAR -->
                    </li>
                </ul>
            </div>
            <!-- /USER RATING -->
        </div>
        <!-- /PRODUCT ITEM -->
        @endforeach

    </div>
    <!-- /PRODUCT LIST -->
</div>
<!-- /PRODUCT SHOWCASE -->

<div class="clearfix"></div>

<!-- PAGER -->
{{ $Service->links() }}