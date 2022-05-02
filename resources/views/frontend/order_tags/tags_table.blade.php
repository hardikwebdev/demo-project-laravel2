<table class="table table-hover cus-account-table" >
    <thead class="tag_head">
        <tr>
            <th width="40%" class="register-text-dark-black font-14">Tag Name</th>
            <th width="20%" class="text-center register-text-dark-black font-14">Count</th>
            <th width="40%" class="text-right register-text-dark-black font-14">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if(count($tags) > 0)
        @foreach( $tags as $tag )
        <tr class="tr_{{$tag->id}} word_break">
            <td class="align-middle">
                <div id="tag_value{{$tag->id}}" class="tag_value_class">
                  <span class="register-bg-medium-gray py-2 px-3 text-nowrap" id="tag_name_list_{{$tag->id}}"> {{ $tag->tag_name }} </span> 
                </div>
                <div id="tag_form{{$tag->id}}" class="tag_form_class hide">
                    <input type="hidden" name="id" value="{{$tag->secret}}" id="secret_{{$tag->id}}">
                    <div class="row">
                        <div class="col-md-10 form-group">
                            <input type="text" name="tag_name" class="form-control" value="{{$tag->tag_name}}" id="tag_name_{{$tag->id}}">
                        </div>
                    </div>
                </div>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="d-inline-block"> {{ count($tag->tag_orders) }} </div>
                    @if(count($tag->tag_orders) > 0)
                    <a href="javascript:void(0)" class="clear_orders_btn ml-3 cus-register-shadow py-2 px-3 d-inline-block"
                        data-url="{{ route('clear_orders_from_tag')}}"
                        data-secret="{{$tag->secret}}" data-toggle="tooltip" data-placement="top"
                        title="Remove from all orders"><i
                        class="fa fa-trash text-color-3"></i></a>
                    @endif
                </div>
            </td>
            <td class="text-right">
                <div class="d-flex align-items-center justify-content-end">
                    <a href="javascript:void(0)" class="save_tag_btn cus-register-shadow py-2 px-3 mr-3 hide" id="save_{{$tag->id}}" data-tagid="{{$tag->id}}" data-url="{{ route('save_edit_order_tag') }}" data-toggle="tooltip" data-placement="top" title="Save"><i class="fas fa-save text-color-3"></i></a>

                    <a href="javascript:void(0)" class="rename_tag_btn cus-register-shadow py-2 px-3 d-inline-block" data-tagid="{{$tag->id}}" id="edit_{{$tag->id}}" data-toggle="tooltip" data-placement="top" title="Edit"><i class="fa fa-pencil-alt text-color-3"></i></a>

                    <a href="javascript:void(0)" class="delete_tag_btn ml-3 cus-register-shadow py-2 px-3 d-inline-block" data-url="{{ route('delete_order_tag') }}" data-secret="{{$tag->secret}}" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fa fa-trash text-color-3"></i></a>
                </div>
            </td>
        </tr>
        @endforeach
        <tr>
            <td colspan='6' class="register-text-light-gray font-14 text-center account-table-footer register-table-footer border-radius-6px">
                @if(count($tags))
                {{ $tags->appends(['search' =>
                isset($_GET['search'])?$_GET['search']:''])->links("pagination::bootstrap-4") }}
                <div>Showing {{($tags->currentpage()-1)*$tags->perpage()+1}} to {{$tags->currentpage()*$tags->perpage()}}
                    of  {{$tags->total()}} entries
                </div>
                @endif
            </td>
        </tr>
        @else
        <tr>
            <td colspan='6' class="text-danger text-center">No tags added yet
            </td>
        </tr>
        @endif
    </tbody>
</table>