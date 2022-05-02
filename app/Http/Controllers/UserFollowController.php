<?php

namespace App\Http\Controllers;

use App\User;
use App\Order;
use App\UserFollow;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Session;

class UserFollowController extends Controller
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

    /**
     * @param Request $request
     * @param $user_id
     * @return Application|ResponseFactory|JsonResponse|Response
     *
     */
    public function store(Request $request)
    {
       $id = User::getDecryptedId($request->user_id);
       
        if(!$id) {  
            return response()->json([
                'status' => false,
            ]);
        }

        $follower = UserFollow::select('id', 'status')->whereUserId($id)->whereFollowerId($this->uid)->first();

        if($follower){
            if($follower->status == UserFollow::FOLLOW){

                $follower->status = UserFollow::UNFOLLOW;
                $follower->save();

                return response()->json([
                    'status' => true,
                    'follow' => false
                ]); 
            }else{
                
                $follower->status = UserFollow::FOLLOW;
                $follower->save();

                return response()->json([
                    'status' => true,
                    'follow' => true
                ]); 
            }

        } else {
              
               $userFollow = new UserFollow;
               $userFollow->user_id = $id;
               $userFollow->follower_id = $this->uid;
               $userFollow->status = UserFollow::FOLLOW;
               $userFollow->save();
          

            return response()->json([
                'status' => true,
                'follow' => true
            ]); 
        }

    }

    /* List of followers */
    public function followers(Request $request, $username){
        $user = User::select('id')->where('username',$username)->where('status',1)->where('is_delete',0)->first();
        if($user && $this->uid == $user->id){
            /* Block users */
            $block_users = User::getBlockedByIds();
            /* END Block users */ 

            $followers = UserFollow::select('users.id', 'user_follows.follower_id', 'user_follows.status','users.username','users.profile_photo','users.photo_s3_key','users.seller_level','users.name')
                ->where('user_follows.user_id',$user->id)
                ->where('user_follows.status',1)
                ->where('users.status',1)
                ->where('users.soft_ban',0)
                ->where('users.is_delete',0)
                ->join('users', 'user_follows.follower_id', '=', 'users.id');
                /* Check Block user*/
                if($block_users){
                    $followers = $followers->whereNotIn('users.id',$block_users);
                }
                /* END Check Block user*/
                $followers = $followers->paginate(20)
                ->appends($request->all());
                
            if($request->ajax()){
                return view('frontend.seller.single_follower_profile',compact('followers','username'))->render();
            }

            return view('frontend.seller.followers',compact('followers','username'));
        }

        Session::flash('tostError','Something went wrong.');
        return redirect()->back();

    }

    /* List of followings */
    public function followings(Request $request, $username){
        $user = User::select('id')->where('username',$username)->where('status',1)->where('is_delete',0)->first();
        if($user && $this->uid == $user->id){
            /* Block users */
            $block_users = User::getBlockedByIds();
            /* END Block users */

            $followers = UserFollow::select('users.id', 'user_follows.user_id',  'user_follows.follower_id', 'user_follows.status','users.username','users.profile_photo','users.photo_s3_key','users.seller_level','users.name')
                ->where('user_follows.follower_id',$user->id)
                ->where('user_follows.status',1)
                ->where('users.soft_ban',0)
                ->where('users.status',1)
                ->where('users.is_delete',0)
                ->join('users', 'user_follows.user_id', '=', 'users.id');
                /* Check Block user*/
                if($block_users){
                    $followers = $followers->whereNotIn('users.id',$block_users);
                }
                /* END Check Block user*/
                $followers = $followers->paginate(20)
                ->appends($request->all());

            if($request->ajax()){
                return view('frontend.seller.single_follower_profile',compact('followers','username'))->render();
            }

            return view('frontend.seller.followers',compact('followers','username'));
        }

        Session::flash('tostError','Something went wrong.');
        return redirect()->back();

    }
}
