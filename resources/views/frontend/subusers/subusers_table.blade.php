<table class="table manage-sale-tabel">
    <thead>
        <tr class="custom-bold-header">
            <!-- <td class="width180">Created On</td> -->
            <td class="register-text-dark-black font-14 border-top-0">Name</td>
            {{-- <td class="text-center">Username</td> --}}
            <!-- <td class="text-center1">Email</td> -->
            <td class="register-text-dark-black font-14 border-top-0 text-center">Status</td>
            <td class="register-text-dark-black font-14 border-top-0">As Seller</td>
            <td class="register-text-dark-black font-14 border-top-0">As Buyer</td>
            <td class="register-text-dark-black font-14 border-top-0 text-right">Action</td>
        </tr>
    </thead>
    <tbody>
        @if(count($model))
        @foreach($model as $row)
        <tr>
            <!-- <td>
                {{date('d M Y',strtotime($row->created_at))}}
            </td> -->
            <td>
                <div class="register-text-dark-black font-14"> {{$row->Name}} </div>
                <div class="register-text-dark-black font-12"> {{$row->email}} </div>
            </td>
            {{-- <td class="text-center">
                {{$row->username}}
            </td> --}}
            <!-- <td class="text-center1">
                {{$row->email}}
            </td> -->
            <td class="text-center">
                @if ($row->status == 1)
                <div class="register-bg-light-primary	 py-2 px-3 rounded-pill"> Active </div>
                @else
                <div class="register-bg-medium-gray py-2 px-3 rounded-pill"> Disabled </div>
                @endif
                
            </td>
            <td class="text-center">
                <div class="form-group cusswitch mb-0">
                    <label class="cus-switch togglenotification cus-seller-switch">
                        @if(Auth::check() && Auth::user()->is_premium_seller() == true)
                        {{ Form::checkbox('change_seller_switch', 1, $row->sub_user_permissions->is_seller_subuser,["class"=>"toggle-input change_seller_switch","data-subuser_id" => $row->id]) }}
                        <span class="checkslider round"></span>
                        @else 
                        {{ Form::checkbox('change_seller_switch', 1, $row->sub_user_permissions->is_seller_subuser,["class"=>"toggle-input",'disabled'=>'true']) }}
                        <span class="checkslider round disabledslider"></span>
                        @endif
                    </label>
                </div>
            </td>
            <td class="text-center">
                <div class="form-group cusswitch mb-0">
                    <label class="cus-switch togglenotification cus-seller-switch">
                        @php
                        $class = ($row->sub_user_permissions->is_buyer_subuser == 1)? "change_buyer_switch" : "change_permission_link";
                        @endphp
                        {{ Form::checkbox('change_buyer_switch', 1, $row->sub_user_permissions->is_buyer_subuser,["class"=>"toggle-input is_buyer_switch ".$class. " sec-".$row->secret,"data-subuser_id" => $row->id, "data-info" => $row->sub_user_permissions,'data-is_unchecked'=> $row->secret ]) }}
                        <span class="checkslider round"></span>
                    </label>
                </div>
            </td>
            <td class="text-right">
                <div class="d-flex align-items-center justify-content-end">
                    <a href="javascript:void(0)" class="edit_sub_users_link cus-register-shadow py-2 px-3 d-inline-block" data-info="{{$row}}">
                        <i class="fa fa-pencil-alt text-color-3"></i>
                    </a>
                    &nbsp;&nbsp;
                    <a href="javascript:void(0)" class="security_link cus-register-shadow py-2 px-3 d-inline-block" data-info="{{$row}}">
                        <i class="fas fa-lock fa-lg fa-security text-color-3"></i>
                    </a>
                    &nbsp;&nbsp;
                    @if($row->sub_user_permissions->is_buyer_subuser == 1)
                    <a href="javascript:void(0)" class="change_permission_link cus-register-shadow py-2 px-3 d-inline-block" data-info="{{$row->sub_user_permissions}}" data-toggle="tooltip" data-placement="bottom" title="Change Permissions">
                        <i class="fas fa-key fa-lg fa-security text-color-3"></i>
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
        <tr>
            <td colspan='6' class="register-text-light-gray font-14 text-center account-table-footer register-table-footer">
                @if(count($model))
                    {{ $model->links("pagination::bootstrap-4") }}
                    <div>Showing {{($model->currentpage()-1)*$model->perpage()+1}} to {{$model->currentpage()*$model->perpage()}}
                        of  {{$model->total()}} entries
                    </div>
                @endif
            </td>
        </tr>
        @else	
        <tr>
            <td colspan="7" class="text-center">
                No sub users added yet
            </td>
        </tr>
        @endif

    </tbody>
</table>

<div class="clearfix"></div>