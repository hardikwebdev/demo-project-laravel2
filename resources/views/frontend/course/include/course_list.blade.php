@if(count($Courses)>0)
    <div class="row summary mx-0">
        <div class="col-12 text-center my-orders cus-container-two table-responsive-sm">  
            <table class="table table-borderless table-hover text-left">
                <thead>
                    <tr class="manage-sale-head default-td custom-bold-header">
                        <th class="font-12 text-color-4 font-weight-normal min-w-335" width="50%">Information</th>
                        <th class="font-12 text-color-4 font-weight-normal" width="10%">Price</th>
                        <th class="font-12 text-color-4 font-weight-normal" width="15%">Last Updated</th>
                        <th class="font-12 text-color-4 font-weight-normal" width="25%">Action</th>
                    </tr>
                </thead>
                <tbody class="order-tab-body">
                    @foreach($Courses as $row)
                        <tr>
                            {{-- Course Information --}}
                            <td class="font-14 text-color-6 text-left d-flex">
                                <div class="service-image">
                                    @if(isset($row->images[0]))
                                        @if($row->images[0]->photo_s3_key != '')
                                            <img alt="product-image" class="img-fluid img-max-height"  src="{{$row->images[0]->thumbnail_media_url}}">
                                        @else
                                            <img alt="product-image" class="img-fluid img-max-height"  src="{{url('public/services/images/'.$row->images[0]->media_url)}}">
                                        @endif
                                    @endif
                                </div>
                                <div class="font-13 font-weight-bold ml-2 w-70">
                                    <a class="text-color-6 view-order-detail-btn" href="{{route('course.update_overview',$row->seo_url)}}">{{$row->title}}</a>
                                    @php
                                    $text_color = "text-primary";
                                    if($row->status == 'paused'){
                                        $text_color = "text-warning";
                                    }elseif($row->status == 'draft'){
                                        $text_color = "text-success";
                                    }elseif($row->status == 'pending'){
                                        $text_color = "text-info";
                                    }elseif($row->status == 'denied'){
                                        $text_color = "text-danger";
                                    }
                                    @endphp
                                    <div class="{{$text_color}}">{{show_service_status($row->status)}}</div>

                                    <h6>
                                    @if($row->is_approved == "0")
                                    <span class="badge badge-info" >Pending Admin Approval</span>
                                    @elseif($row->is_approved == "1")
                                    @else
                                    <span class="badge badge-danger">Admin Rejected</span>
                                    @endif
                                    </h6>

                                    </div>
                                </div>
                            </td>
                            {{-- Price --}}
                            <td class="font-13 font-weight-bold">
                                {{isset($row->lifetime_plans->price)?'$'.$row->lifetime_plans->price:''}}
                            </td>
                            {{-- Last Uodated Date --}}
                            <td> {{date('d M Y',strtotime($row->last_updated_on))}} </td>
                            <td class="font-13 text-color-6">
                                <div class="d-flex">
                                    @if($row->status != "denied" && $row->status != "permanently_denied")
                                        <div class="service-btn align-items-start">
                                            <form class="form-inline" action="#">
                                                <div class="form-group">
                                                    <select class="course_action form-control custom_select_width" data-id="{{$row->id}}" name="status">
                                                        <option value="" selected="selected">Action</option>
                                                        <option value="{{route('course.update_overview',$row->seo_url)}}">Edit</option>
                                                        <option value="{{route('course.remove',$row->seo_url)}}">Delete</option>
                                                       
                                                        @if($row->status != 'paused')
                                                        <option value="pause">Pause</option>
                                                        @elseif($row->current_step == '5')
                                                        <option value="{{route('change_status',['id'=>$row->id,'status'=>'active'])}}">Active</option>
                                                        @else
                                                        <option value="{{route('change_status',['id'=>$row->id,'status'=>'draft'])}}">Draft</option>
                                                        @endif

                                                        @if($row->status == 'paused' && $row->current_step >= '5')
                                                        <option value="{{route('course.publish',$row->seo_url)}}">Reactivate</option>
                                                        @endif

                                                        @if($row->status == 'active')
                                                            <option value="{{route('course.section',$row->seo_url)}}">Edit Content</option>
                                                            @if($row->is_recurring == 0 && Auth::user()->is_course_training_account() == false)
                                                                <option value="{{ route('coupan',[ 'id' => $row->id, 'type' => $row->lifetime_plans->plan_type ] )}}">Coupon</option>
                                                            @endif
                                                        @endif

                                                        @if((Auth::user()->is_premium_seller($parent_uid) == true && $row->is_recurring == 0 && $row->is_course == 0))
                                                            <option value="{{route('offer_volume_discount',$row->id)}}">Volume Discount</option>
                                                        @endif

                                                    </select>
                                                </div>
                                            </form>
                                            @if($row->current_step >= 5 && $row->uid == Auth::id() && ($row->status == 'draft' || $row->status == 'paused' || $row->is_approved == "0"))
                                                <div class="prompt-btn"> 
                                                    <a href="{{route('course_details',[$row->user->username,$row->seo_url])}}" target="_blank">
                                                        <button type="button" class="btn">Preview</button>
                                                    </a>
                                                </div>
                                            @endif
                                            @if( $row->status == 'active' && Auth::user()->parent_id == 0 && $row->is_approved == '1')
                                                <div class="prompt-btn"> 
                                                    <button type="button" data-clipboard-text="{{route('course_details',[$row->user->username,$row->seo_url])}}" class="btn copy_btn">Copy URL</button>
                                                </div>
                                            @endif
                                        </div>
                                    @else 
                                        <div class="service-btn">
                                            <form class="form-inline" action="#">
                                                <div class="form-group">
                                                    <select class="course_action form-control custom_select_width" id="category_id" data-id="{{$row->id}}" name="status">
                                                        <option value="" selected="selected">Action</option>
                                                        @if($row->status == "denied" && App\User::is_soft_ban() == 0)
                                                        <option value="{{route('course.update_overview',$row->seo_url)}}">Edit</option>
                                                        @endif
                                                        <option value="{{route('course.remove',$row->seo_url)}}">Delete</option>
                                                    </select>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="text-center mt-3 order-pagination text-white">
                @if(isset($Courses))
                    {{ $Courses->links("pagination::bootstrap-4") }}
                @endif
            </div>
        </div>
    </div>
@else
    <div class="col-md-12 text-center my-5">
        <div class="overlayer"></div>
        <img width="140" src="{{url('public/frontend/images/empty_item.svg')}}">
        <h3 class="text-center">No course available.</h3>
    </div>
@endif