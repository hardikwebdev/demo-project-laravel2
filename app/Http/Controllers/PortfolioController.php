<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Portfolio;
use App\Service;
use App\User;
use Auth;
use AWS;
use Session;
use Validator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Thumbnail;

class PortfolioController extends Controller
{
    private $uid;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            if(Auth::check()) { 
                $this->uid = Auth::user()->id;
                if(Auth::user()->parent_id != 0){
                    $this->uid = Auth::user()->parent_id;
                }
            }
            return $next($request);
        });
    }

    public function index(Request $request,$username){
        $portfolios = Portfolio::select('portfolios.*')
            ->where('portfolios.is_delete', 0)
            ->where('users.username', $username)
            ->join('users', 'portfolios.user_id', '=', 'users.id')
            ->orderBy('sort_by','ASC')
            ->get();

        $user = User::select('id')->where('username',$username)->first();
        if(!$user){
            return redirect()->back();
        }

        if(Auth::check() && Auth::user()->username == $username){

            $Service = Service::select('id')
                ->where('status', 'active')
                ->where('is_approved', 1)
                ->where('is_private', 0)
                ->where('is_job', 0)
                ->where('is_custom_order', 0)
                ->where('uid', $user->id)
                ->count();

            if($Service > 0){
                /*Delete temp file from session*/
                $this->delete_temp_file();
                return view('frontend.portfolio.edit',compact('portfolios','username'));
            }else{
                return redirect()->back();
            }
        }else{
            /* Check Blocked Users */
            $block_users = User::getBlockedByIds();
            if(in_array($user->id,$block_users)){
                abort(401);
            }
            /*End Check Blocked user*/
            return view('frontend.portfolio.index',compact('portfolios','username'));
        }
    }

    /*Create New Portfolio*/
    public function create(Request $request){
        if($request->method() == 'POST'){
            $validator = Validator::make($request->all(), [
                'title'         => 'required|max:120',
                'description'   => 'required|max:120',
            ]);
            if ($validator->fails()) {
                Session::flash('tostError', $validator->errors()->first());
                return redirect()->route('portfolio',Auth::user()->username); 
            }

            $temp_media = Session::get('portfolio_media');
            if(!empty($temp_media)){
                if($temp_media['originalName'] != $request->upload_media){
                    Session::flash('tostError', 'Something went wrong.');
                    return redirect()->route('portfolio',Auth::user()->username); 
                }
            }else{
                Session::flash('tostError', 'Something went wrong.');
                return redirect()->route('portfolio',Auth::user()->username); 
            }

            $portfolio_count = Portfolio::select('id')
                ->where('is_delete', 0)
                ->where('user_id', Auth::user()->id)
                ->count();

            if($portfolio_count >= env('PORTFOLIO_LIMITATION')){
                Session::flash('tostError', 'You have create max '.env('PORTFOLIO_LIMITATION').' Projects');
                return redirect()->route('portfolio',Auth::user()->username); 
            }

            $imageKey = md5(Auth::user()->id).'/portfolio/'.md5(time()) . '.' . $temp_media['extension'];
            $result_amazonS3 = $this->saveOnAWS($temp_media['source_file'], $imageKey);
            if ($result_amazonS3) {
                
                $thumb_imageKey = '';
                $result_amazonS3_thumbnail = array();
                if($temp_media['source_url_thumb'] != ''){
                    //create thumbnail
                    $thumb_imageKey = md5(Auth::user()->id).'/portfolio/thumb/'.md5(time()) . '.' . $temp_media['extension'];
                    $result_amazonS3_thumbnail = $this->saveOnAWS($temp_media['source_url_thumb'], $thumb_imageKey);
                }

                if ($result_amazonS3_thumbnail || $temp_media['source_url_thumb'] == '') {
                    
                    unlink($temp_media['source_file']);
                    unlink($temp_media['source_url_thumb']);
                    Session::forget('portfolio_media');

                    $last_sort_id = Portfolio::select('id','sort_by')
                    ->where('is_delete', 0)
                    ->where('user_id',Auth::user()->id)
                    ->orderBy('sort_by','desc')
                    ->first();

                    if(!$last_sort_id){
                        $last_id = 1;
                    }else{
                        $last_id = $last_sort_id->sort_by+1;
                    }

                    $create = new Portfolio;
                    $create->user_id        = Auth::user()->id;
                    $create->title          = $request->title;
                    $create->description    = $request->description;
                    $create->thumbnail_url  = (!empty($result_amazonS3_thumbnail))? $result_amazonS3_thumbnail['ObjectURL'] : '';
                    $create->thumb_s3_key   = ($thumb_imageKey != '')? $thumb_imageKey : '';
                    $create->media_s3_key   = $imageKey;
                    $create->media_link     = $result_amazonS3['ObjectURL'];
                    $create->media_type     = $temp_media['media_type'];
                    $create->media_size     = $temp_media['media_size'];
                    $create->original_name  = $temp_media['originalName'];
                    $create->media_mime     = $temp_media['media_mime'];
                    $create->sort_by        = $last_id;
                    $create->save();
                    Session::flash('tostSuccess', 'Project created successfully');
                }else{
                    $this->remove_file_on_aws($imageKey); // Delete 
                    Session::flash('tostError', 'Something went wrong. Please try again.');
                }

            }else{
                Session::flash('tostError', 'Something went wrong. Please try again.');
            }
            return redirect()->route('portfolio',Auth::user()->username);
        }else{
            if($request->ajax()){
                $view = view('frontend.portfolio.add_model')->render();
                return response()->json(['success'=>true,'status'=>200,'html'=>$view]);
            }else{
                return redirect('404'); 
            }
        }
    }
    
    /*Update Portfolio*/
    public function update(Request $request,$id){
        $id = Portfolio::getDecryptedId($request->id);
        $portfolio = Portfolio::where('id',$id)->where('user_id',Auth::user()->id)->first();
        
        if($request->method() == "GET"){
            if($portfolio){
                $response['success']    = true;
                $response['status']     = 200;
                $response['html']       = view('frontend.portfolio.edit_model',compact('portfolio'))->render();
            }else{
                $response['success']    = false;
                $response['status']     = 401;
            }
            return response()->json($response);
        }

        if($portfolio){
            $validator = Validator::make($request->all(), [
                'title'         => 'required|max:120',
                'description'   => 'required|max:120',
            ]);

            if ($validator->fails()) {
                Session::flash('tostError', $validator->errors()->first());
                return redirect()->route('portfolio',Auth::user()->username); 
            }

            $temp_media = Session::get('portfolio_media');
            $imageKey = "";
            $thumb_imageKey = "";
            $result_amazonS3 = array();
            $result_amazonS3_thumbnail = array();

            if(!empty($temp_media)){
                if($temp_media['originalName'] != $request->upload_media){
                    Session::flash('tostError', 'Something went wrong.');
                    return redirect()->route('portfolio',Auth::user()->username); 
                }

                $imageKey = md5(Auth::user()->id).'/portfolio/'.md5(time()) . '.' . $temp_media['extension'];
                $result_amazonS3 = $this->saveOnAWS($temp_media['source_file'], $imageKey);
                if ($result_amazonS3) {
                    if($temp_media['source_url_thumb'] != ''){
                        //create thumbnail
                        $thumb_imageKey = md5(Auth::user()->id).'/portfolio/thumb/'.md5(time()) . '.png';
                        $result_amazonS3_thumbnail = $this->saveOnAWS($temp_media['source_url_thumb'], $thumb_imageKey);
                    }

                    if($result_amazonS3_thumbnail || $temp_media['source_url_thumb'] == ''){
                        
                        $this->remove_file_on_aws($portfolio->media_s3_key); // Delete old image
                        if($portfolio->thumb_s3_key != ''){
                            $this->remove_file_on_aws($portfolio->thumb_s3_key); // Delete old image
                        }

                        unlink($temp_media['source_file']);
                        unlink($temp_media['source_url_thumb']);
                        Session::forget('portfolio_media');

                        $portfolio->thumbnail_url  = (!empty($result_amazonS3_thumbnail))? $result_amazonS3_thumbnail['ObjectURL'] : '';
                        $portfolio->thumb_s3_key   = ($thumb_imageKey != '')? $thumb_imageKey : '';
                        $portfolio->media_s3_key   = $imageKey;
                        $portfolio->media_link     = $result_amazonS3['ObjectURL'];
                        $portfolio->media_type     = $temp_media['media_type'];
                        $portfolio->media_size     = $temp_media['media_size'];
                        $portfolio->original_name  = $temp_media['originalName'];
                        $portfolio->media_mime     = $temp_media['media_mime'];
                    }else{
                        $this->remove_file_on_aws($imageKey); // Delete old image
                        Session::flash('tostError', 'Something went wrong. Please try again.');
                        return redirect()->route('portfolio',Auth::user()->username);                    
                    }

                }else{
                    Session::flash('tostError', 'Something went wrong. Please try again.');
                    return redirect()->route('portfolio',Auth::user()->username);                    
                }
            }

            $portfolio->title = $request->title;
            $portfolio->description = $request->description;
            $portfolio->save();
            Session::flash('tostSuccess', 'Project updated successfully');
        }else{
            Session::flash('tostError', 'Something went wrong. Please try again.');
        }
        return redirect()->route('portfolio',Auth::user()->username);
    }

    /*Delete Portfolio*/
    public function delete(Request $request,$id){
        $id = Portfolio::getDecryptedId($id);
        $delete = Portfolio::where('id',$id)->where('user_id',Auth::user()->id)->where('is_delete',0)->first();
        if($delete){
            $delete->is_delete = 1;
            $delete->save();
            $this->remove_file_on_aws($delete->media_s3_key);
            if($delete->thumb_s3_key != ''){
                $this->remove_file_on_aws($delete->thumb_s3_key);
            }
            Session::flash('tostSuccess', 'Project deleted successfully');
        }else{
            Session::flash('tostError', 'Something went wrong. Please try again.');
        }
        return redirect()->route('portfolio',Auth::user()->username);
    }

    /*File upload function*/ 
    public function upload_media(Request $request){

        $validator = Validator::make($request->all(), [
            'file' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['success'=>false,'status'=>401,'message'=>$validator->errors()->first()]);
        }else{
            $ismime = $request->file->getClientMimeType();
            if(strstr($ismime, "image/")){
                $validator = Validator::make($request->all(), [
                    'file' => 'mimetypes:image/jpg,image/jpeg,image/png|max:20480'
                ],
                [
                    'file.max' => 'File is larger than 20MB'
                ]
            );
            }else{
                $validator = Validator::make($request->all(), [
                    'file' => 'mimetypes:video/mp4,video/x-msvideo,video/quicktime,video/webm|max:256000'
                ],
                [
                    'file.max' => 'File is larger than 250MB'
                ]
            );
            }

            if ($validator->fails()) {
                return response()->json(['success'=>false,'status'=>401,'message'=>$validator->errors()->first()]);
            }
        }

        try{        
            $originalName   = $request->file->getClientOriginalName();
            $mime       = $request->file->getClientMimeType();
            $filesize   = formatBytes($request->file->getSize(),2);
            $ext        = $request->file->extension();
            $fileName   = time().rand().'.'.$ext;
            $request->file->move(public_path('seller/portfolio'), $fileName);
            $SourceFile = public_path('seller/portfolio').'/'.$fileName;
            $image_extension = ['jpg','jpeg','png'];
            $source_url_thumb = '';
            $thumbnail_url = '';

            //Generate thumbnail
            $thumb_name = 'thumb_'.time().rand().'.png';
            $destination_path = public_path('seller/portfolio');
            
            if (!in_array($ext,$image_extension)) {
                // Video thumbnail
                $thumbnail_status = Thumbnail::getThumbnail($SourceFile,$destination_path,$thumb_name,env('TIME_TO_TAKE_SCREENSHOT'));
                if($thumbnail_status){
                    $source_url_thumb = public_path('seller/portfolio').'/'.$thumb_name;
                }else{
                    return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong. Please try again.']);
                }
            }else{
                // Image thumbnail
                $source_url_thumb = public_path('seller/portfolio').'/'.$thumb_name;
                Storage::disk('portfolio_image')->copy($fileName, $thumb_name);
            }
            // Local Thumbnail Url
            $thumbnail_url = url('public/seller/portfolio/'.$thumb_name);
            $originalImage = Image::make($source_url_thumb);
            $originalImage->fit(env('PORTFOLIO_IMAGE_THUMBNAIL_WIDTH'), env('PORTFOLIO_IMAGE_THUMBNAIL_HEIGHT'),NULL,'top')->save($source_url_thumb,85);
            //End Generate thumbnail

            $temp_media = Session::get('portfolio_media');
            if(!empty($temp_media)){
                unlink($temp_media['media_link']);
                Session::forget('portfolio_media');
            }

            $data['media_link']     = url('public/seller/portfolio/'.$fileName);
            $data['source_file']    = $SourceFile;
            if(strstr($mime, "image/")){
                $data['media_type'] = 'image';
            }else{
                $data['media_type'] = 'video';
            }
            $data['media_size']     = $filesize;
            $data['media_mime']     = $mime;
            $data['originalName']   = $originalName;
            $data['extension']      = $ext;
            $data['source_url_thumb'] = $source_url_thumb;
            session()->put('portfolio_media',$data);
            $data['source_url_thumb'] = $thumbnail_url;
            $data['success']    = true;
            $data['status']     = 200;
            $data['message']    = 'Media uploaded successfully';
            $data['source_file']    = '';
            return response()->json($data);
        }catch(Exception $e){
            return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong. Please try again.']);
        }
    }

    /*Change Ordering*/
    public function change_ordering(Request $request){
        if($request->has('id') && $request->id != ""){
            foreach ($request->id as $key => $id) {
                $id = Portfolio::getDecryptedId($id);
                $portfolio = Portfolio::where('is_delete', 0)->where('user_id',Auth::user()->id)->where('id',$id)->first();
                if($portfolio){
                    $portfolio->sort_by = $key;
                    $portfolio->save();
                }else{
                    break;
                    $response['success'] = false;
                    $response['status'] = 401;
                    return response()->json($response);
                }
            }
            $response['message'] = "Item sorting changed successfully";
            $response['success'] = true;
            $response['status'] = 200;
        }else{
            $response['success'] = false;
            $response['status'] = 401;
        }
        return response()->json($response);
    }

    /*Temp delete function*/ 
    public function delete_temp_media(Request $request){
        $message = $this->delete_temp_file();
        return response()->json(['success'=>true,'status'=>200,'message'=>$message]);
    }

    /* private function for save image on aws */
    function saveOnAWS($SourceFile, $imageKey) {
        try{        
            $s3 = AWS::createClient('s3');
            $bucket = env('bucket_user');
            $result_amazonS3 = $s3->putObject([
                'Bucket'        => $bucket,
                'Key'           => $imageKey,
                'SourceFile'    => $SourceFile,
                'StorageClass'  => 'REDUCED_REDUNDANCY',
                'ACL'           => 'public-read',
            ]);
        }catch(Aws\S3\Exception\S3Exception $e){
            $result_amazonS3 = $e;
        }
        unlink($source_url);
        return $result_amazonS3;
    }

    /* private function to remove file */
    function remove_file_on_aws($keyData){
        $s3 = AWS::createClient('s3');
        $bucket = env('bucket_user');
        try {
            $result_amazonS3= $s3->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $keyData,
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            $result_amazonS3['ObjectURL'] = '';
        }
        return true;
    }

    /* private function delete temp file */
    function delete_temp_file(){
        $message = 'File not found';
        $temp_media = Session::get('portfolio_media');
        if(!empty($temp_media)){
            unlink($temp_media['source_file']);
            unlink($temp_media['source_url_thumb']);
            Session::forget('portfolio_media');
            $message = 'File removed successfully';
        }
        return $message;
    }
}
