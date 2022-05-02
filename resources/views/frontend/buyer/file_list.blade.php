<table class="table table-borderless table-responsive-sm mt-4">
    <tbody class="border-top-gray" id="get-total-attachement" data-total="{{$UserFiles->total()}}">
        @foreach($UserFiles as $row)
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
            <td class="font-14 text-color-4 word-break">
                <div>
                    <h1 class="font-16 text-color-2 font-weight-bold">{{remove_timestamp_from_filename($row->filename)}}</h1>
                    <h3 class="font-14 text-color-4">Added by {{$row->user->username}}</h3>
                </div>
            </td>
            <td class="font-14 text-color-4">{{date('M d, Y',strtotime($row->created_at))}}</td>
            <td class="font-14 text-color-4">{{bytesToHuman($row->filename_size)}}</td>
            <td class="font-14 text-color-4">
                @if($row->photo_s3_key != '')
                    <a class="download-icon" title="Download" href="{{route('download_files_s3')}}?bucket={{env('bucket_order')}}&key={{$row->photo_s3_key}}&filename={{$row->filename}}">
                        <img src="{{url('public/frontend/images/download.png')}}" alt="">
                    </a>
                @else
                    <a class="download-icon" title="Download" href="{{route('download_files',[$row->id])}}" download>
                        <img src="{{url('public/frontend/images/download.png')}}" alt="">
                    </a>
                @endif
            </td>
            <td class="font-14 text-color-4">
                @if($row->uid == $parent_uid)
                    @if($row->photo_s3_key != '')
                        <a href="javascript:void(0);" class="delete-icon notification-close" data-id="{{$row->id}}" data-bucket={{env('bucket_order')}} data-url="{{route('removefile')}}">
                            <img src="{{url('public/frontend/images/Trash.png')}}" alt="">
                        </a>
                    @else
                        <a href="javascript:void(0);" class="delete-icon notification-close" data-id="{{$row->id}}" data-url="{{route('removefile')}}">
                            <img src="{{url('public/frontend/images/Trash.png')}}" alt="">
                        </a>
                    @endif
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div>
    {!! $UserFiles->render() !!}
</div>