@if(count($seller_work) > 0)
<table class="table table-borderless table-responsive-sm mt-4">
    <tbody>
        @foreach($seller_work as $row)
        <tr>
            <td class="font-14 text-color-4  text-left">
                @php 
                $file_type = check_file_type($row->filename);
                @endphp
                @if($file_type == 'image')
                    <img src="{{url('public/frontend/images/image.png')}}" alt="">
                @elseif($file_type == 'file')
                    <img src="{{url('public/frontend/images/File-text.png')}}" alt="">
                @else 
                    <img src="{{url('public/frontend/images/File.png')}}" alt="">
                @endif
            </td>
            <td class="font-14 text-color-4 wordbreack">
                <h1 class="font-16 text-color-2 font-weight-bold">{{remove_timestamp_from_filename($row->filename)}}</h1>
            </td>
            <td class="font-14 text-color-4">{{($row->file_size)? bytesToHuman($row->file_size) : '-'}}</td>
            <td class="font-14 text-color-4">
                @if($row->photo_s3_key != '')
                <a href="{{route('download_files_s3')}}?bucket={{env('bucket_order')}}&key={{$row->photo_s3_key}}&filename={{$row->filename}}" download>
                    <img src="{{url('public/frontend/images/download.png')}}" alt="">
                </a>
                @else
                <a href="{{route('download_source',[$row->order_id,$row->id])}}" download>
                    <img src="{{url('public/frontend/images/download.png')}}" alt="">
                </a>
                @endif
            </td>
            @if(isset($is_delete) && $is_delete == 1)
            <td class="font-14 text-color-4">
                @if($row->photo_s3_key != '')
                    <a href="javascript:void(0);" class="delete-icon remove-delivered-file delete-{{$row->photo_s3_key}}" data-name="{{$row->photo_s3_key}}" data-bucket={{env('bucket_order')}} data-url="{{route('delete_file')}}">
                        <img src="{{url('public/frontend/images/Trash.png')}}" alt="">
                    </a>
                @else
                    <a href="javascript:void(0);" class="delete-icon remove-delivered-file" data-name="{{$row->filename}}" data-bucket="" data-url="{{route('delete_file')}}">
                        <img src="{{url('public/frontend/images/Trash.png')}}" alt="">
                    </a>
                @endif
            </td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
@endif