@if(count($downloadable_contents) > 0)
<table class="table table-borderless table-responsive-sm mt-4">
    <tbody>
        @foreach($downloadable_contents as $row)
        <tr id="resource-{{$row->secret}}">
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
                <h1 class="font-16 text-color-2 font-weight-bold">{{$row->filename}}</h1>
            </td>
            <td class="font-14 text-color-4">{{($row->file_size)? $row->file_size : '-'}}</td>
            <td class="font-14 text-color-4">
                @if($row->file_s3_key != '')
                <a href="{{route('download_files_s3')}}?bucket={{env('bucket_course')}}&key={{$row->file_s3_key}}&filename={{$row->filename}}" download>
                    <img src="{{url('public/frontend/images/download.png')}}" alt="">
                </a>
                @endif
            </td>
            <td class="font-14 text-color-4">
                <a href="javascript:void(0);" class="delete-icon remove-downloadable-file" data-id="{{$row->secret}}" data-url="{{route('delete_downloadable_file',$row->secret)}}">
                    <img src="{{url('public/frontend/images/Trash.png')}}" alt="">
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div id="resourses-paginate">
    {{$downloadable_contents->links()}}
</div>
@endif