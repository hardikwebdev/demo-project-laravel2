<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use App\BuyerOrderTags;
use App\Order;
use Auth;
use Validator;
use App\BuyerOrderTagDetails;

class OrderTagController extends Controller
{
	private $uid;

	public function __construct(){
		$this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            if(Auth::user()->parent_id != 0){
                $this->uid = Auth::user()->parent_id;
            }
            return $next($request);
        });
	}

	public function order_tags(Request $request){
		$uid = $this->uid;
		$search = $request->search ?? '';
		$tags = BuyerOrderTags::where('buyer_id', $uid);
		
		if(strlen($search) > 0) {
			$tags = $tags->where('tag_name','like', '%' . $search . '%');
		}
		$tags = $tags->paginate(10);
		if($request->ajax())
		{
			return view('frontend.order_tags.tags_table',compact('tags'))->render();
		}
		return view('frontend.order_tags.manage_tags',compact('tags'));
	}
	

	public function save_add_order_tag(Request $request){
        $validator = Validator::make($request->all(), array(
            'tag_name' => 'required|max:50',
        ));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

		$check_tag = BuyerOrderTags::where('buyer_id',$this->uid)->where('tag_name',$request->tag_name)->select('id')->first();

		if(is_null($check_tag)) {
			$tag = new BuyerOrderTags;
			$tag->buyer_id = $this->uid;
			$tag->tag_name = $request->tag_name;
			$tag->save();

			$message = 'Tag added successfully';
			$request->session()->flash('errorSuccess', $message);
		} else {
			$message = 'This tag name is already exists';
			$request->session()->flash('errorFails', $message);
		}

        return redirect()->route('order_tags');
	}

	public function save_edit_order_tag(Request $request){
		$uid = $this->uid;
		$tagid = BuyerOrderTags::getDecryptedId($request->id);
		$validator = Validator::make($request->all(), array(
            'tag_name' => 'required|max:50',
            'id' => 'required',
        ));

        if ($validator->fails()) {
			return response([
				'status' => false,
				'message' => $validator->errors()->first()
			]);
        }

		$check_tag = BuyerOrderTags::where('id','!=',$tagid)->where('buyer_id',$uid)->where('tag_name',$request->tag_name)->select('id')->first();

		if(!is_null($check_tag)) {
			return response([
				'status' => false,
				'message' => 'This tag name already exists'
			]);
		}

		$tag = BuyerOrderTags::where('id',$tagid)->where('buyer_id',$uid)->first();

		if(!is_null($tag)){
			$tag->tag_name = $request->tag_name;
			$tag->save();
		}
		return response([
			'status' => true,
			'message' => 'Tag updated successfully'
		]);
	}

	public function delete_order_tag(Request $request){
		$uid = $this->uid;
		$id = BuyerOrderTags::getDecryptedId($request->enc_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		
		$check_tag = BuyerOrderTags::where('id', $id)->where('buyer_id',$uid)->select('id')->first();
		if(!is_null($check_tag)) {
			BuyerOrderTagDetails::where('tag_id', $id)->delete();
			BuyerOrderTags::where('id', $id)->where('buyer_id',$uid)->delete();
			$request->session()->flash('errorSuccess', 'Tag deleted successfully');
		} else {
			$request->session()->flash('errorFails', 'Something went wrong, Please try again.');
		}
		
		return response()->json(['status'=>'success']);
	}
	
	public function get_tags_list(Request $request){
		if(!$request->filled('searchTerm')) {
			return redirect()->back();
		}
		$searchTerm = strtolower($request->searchTerm);

		$tags_data = BuyerOrderTags::where('buyer_id', $this->uid)
		->where('tag_name', 'LIKE', '%' . $searchTerm . '%')
		->select('id','tag_name')
		->get();

		$tags = [];
		foreach ($tags_data as $key => $value) {
			$temp = [];
			$temp['id'] = $value->secret;
			$temp['text'] = $value->tag_name;
			array_push($tags,$temp);
		}

		return response()->json(['status'=>'success','tags'=>$tags]);
	}

	public function clear_orders_from_tag(Request $request) {
		$id = BuyerOrderTags::getDecryptedId($request->enc_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}

		$check_tag = BuyerOrderTags::where('buyer_id',$this->uid)->where('id',$id)->select('id')->first();
		if(!is_null($check_tag)) {
			BuyerOrderTagDetails::where('tag_id', $id)->delete();
			$request->session()->flash('errorSuccess', 'Orders from tag cleared successfully');
		} else {
			$request->session()->flash('errorFails', 'Something went wrong, Please try again.');
		}
		
		return response()->json(['status'=>'success']);
	}

	public function add_order_into_tag(Request $request) {
		$status = "success";
		$message = 'Tag added in order successfully';

		if($request->tagid == "0" && strlen($request->tagname) > 0) {
			$tagid = $request->tagname;
			$check_tag = BuyerOrderTags::where('buyer_id',$this->uid)
			->where('tag_name', $request->tagname)
			->first();
		} else {
			$tagid = BuyerOrderTags::getDecryptedId($request->tagid);
			$check_tag = BuyerOrderTags::where('buyer_id',$this->uid)
			->where('id', $tagid)
			->first();
		}

		if(is_null($check_tag)) {
			//create new tag
			if(strlen($tagid) > 0) {
				/* create tag */
				try {
					$new_tag = new BuyerOrderTags;
					$new_tag->buyer_id = $this->uid;
					$new_tag->tag_name = $tagid;
					$new_tag->save();

					/* create tag and order entry */
					$new_order_tag = new BuyerOrderTagDetails;
					$new_order_tag->tag_id = $new_tag->id;
					$new_order_tag->order_id = $request->orderid;
					$new_order_tag->save();
				} catch(\Exception $e) {
					$status = "error";
					$message = 'Something went wrong, Please try again.';
				}
			}
		} else {
			$tag = BuyerOrderTagDetails::where('tag_id',$check_tag->id)->where('order_id',$request->orderid)->first();
			if(is_null($tag)) {
				$new_order_tag = new BuyerOrderTagDetails;
				$new_order_tag->tag_id = $check_tag->id;
				$new_order_tag->order_id = $request->orderid;
				$new_order_tag->save();
			}
		}
		$OrderTags = BuyerOrderTags::where('buyer_id',$this->uid)->select('tag_name','id')->get();
		$addedTags = BuyerOrderTagDetails::where('order_id',$request->orderid)->pluck('tag_id')->toArray();
		$MostUsedOrderTags = BuyerOrderTags::where('buyer_id',$this->uid)
									->select('tag_name','id')
									->withCount('tag_orders')
									->orderBy('tag_orders_count','desc')
									->limit(10)
									->get();

		return response()->json(['status'=>$status,'message'=>$message,'OrderTags'=>$OrderTags,'addedTags'=>$addedTags,'MostUsedOrderTags'=>$MostUsedOrderTags]);
	}

	public function remove_tag_from_order(Request $request) {
		$orderid = Order::getDecryptedId($request->orderid);
		$check_order = Order::select('id','uid')->find($orderid);

		if(!is_null($check_order) && $check_order->uid == $this->uid) {
			BuyerOrderTagDetails::where('tag_id',$request->tagid)->where('order_id',$orderid)->delete();
			$status = 'success';
			$message = "Tag removed successfully";
		} else {
			$status = 'error';
			$message = "Something went wrong, Please try again.";
		}
		return response()->json(['status'=>$status,'message'=>$message]);
	}

	public function clear_all_tags_from_order(Request $request) {
		$orderid = Order::getDecryptedId($request->orderid);
		$check_order = Order::select('id','uid')->find($orderid);

		if(!is_null($check_order) && $check_order->uid == $this->uid) {
			BuyerOrderTagDetails::where('order_id',$orderid)->delete();
			$status = 'success';
			$message = "Clear all tags successfully";
		} else {
			$status = 'error';
			$message = "Something went wrong, Please try again.";
		}
		return response()->json(['status'=>$status,'message'=>$message]);
	}
}
