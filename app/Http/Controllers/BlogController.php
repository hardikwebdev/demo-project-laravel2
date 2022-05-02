<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\BlogPost;
use App\BlogCategory;
use App\BlogPostMedia;
use App\HomeSlider;
use App\Order;
use App\Service;
use App\User;

class BlogController extends Controller {

    public function blogs(Request $request) {
        //get home slider
		$home_slider = HomeSlider::orderBy('sort_order','asc')->get();
        //get categories 
        User::set_db_connection(env('DB_DATABASE_BLOG'));

        $blog_categories = BlogCategory::where('parent_id',0)->orderBy('category_name','asc')->select('id','category_name','slug')->get();

        $catSlug = 'page';

        // $latest_published = BlogPost::with('media_images','category_associate','category_associate.category' ,'tagAssociate','tagAssociate.tag')
        //                             ->whereHas('category_associate.category', function($q) use($catSlug){
        //                                 $q->where('slug','!=', $catSlug);
        //                             })
        //                             ->where('is_published',1)
        //                             ->orderBy('published_date','desc')
        //                             ->limit(4)
        //                             ->select('id','title','slug','category_id','description','subcategory_id')
        //                             ->get();
            
        $latest_created = BlogPost::with('media_images','category_associate','category_associate.category' ,'tagAssociate','tagAssociate.tag')
                                    ->whereHas('category_associate.category', function($q) use($catSlug){
                                        $q->where('slug','!=', $catSlug)->select('id');
                                    })
                                    ->where('is_published',1)
                                    ->orderBy('id','desc')
                                    ->limit(4)
                                    ->select('id','title','slug','category_id','description','subcategory_id')
                                    ->get();
        $most_used_category = DB::table("blog_categories")
                                    ->where('parent_id',0)
                                    ->select('blog_categories.id','blog_categories.category_name','blog_categories.slug',DB::raw("(select count(blog_posts.id) from blog_posts where blog_posts.category_id=blog_categories.id) as post_count"),DB::raw("(select count(blog_posts.id) from blog_posts where blog_posts.category_id=blog_categories.id) as post_count"))
                                    ->orderBy('post_count','desc')
                                    ->limit(3)
                                    ->get();
        foreach ($most_used_category as $key => $value) {
            $post = BlogPost::with('media_images')->where('category_id',$value->id)->orderBy('id','desc')->select('id','title')->first();
            $value->featured_image = get_blog_image($post);
            $value->latest_post_title = $post->title;
        }
        User::set_db_connection('');

        return view('frontend.blogs.index', compact('home_slider','blog_categories','latest_created','most_used_category'));
    }

    public function blogs_by_category(Request $request,$slug) {
        User::set_db_connection(env('DB_DATABASE_BLOG'));

        $category = BlogCategory::select('id','category_name','slug')->where('slug',$slug)->first();
        if(is_null($category)) {
            User::set_db_connection('');
            return redirect('404');
        }
        $catId =  $category->id;
        $posts = BlogPost::with('media_images','category_associate','category_associate.category' ,'tagAssociate','tagAssociate.tag')->where('is_published',1)
                    ->whereHas('category_associate', function($q) use($catId){
                        $q->where('category_id', $catId)->select('id');
                    })
                    ->orderBy('published_date','desc')
                    ->select('id','title','slug','category_id','description','subcategory_id')
                    ->paginate(9);
        //get categories
        $blog_categories = BlogCategory::where('parent_id',0)->orderBy('category_name','asc')->select('id','category_name','slug')->get();
        User::set_db_connection('');

        if ($request->ajax()) {
			return view('frontend.blogs.blogs_by_category_ajax', compact('posts'))->render();
		}

        return view('frontend.blogs.blogs_by_category', compact('posts','blog_categories','category'));
    }

    public function post_details(Request $request, $slug) {
        User::set_db_connection(env('DB_DATABASE_BLOG'));
        $post = BlogPost::with('media_images','category_associate','category_associate.category' ,'tagAssociate','tagAssociate.tag')->where('is_published',1)->where('slug',$slug)->first();
        if(is_null($post)) {
            User::set_db_connection('');
            return redirect()->route('blogs');
        }
        $blog_categories = BlogCategory::where('parent_id',0)->orderBy('category_name','asc')->select('id','category_name','slug')->get();
        //get recents posts for sidebar
        $catSlug = 'page';
        $recent_posts = BlogPost::with('media_images','category_associate','category_associate.category' ,'tagAssociate','tagAssociate.tag')
                            ->whereHas('category_associate.category', function($q) use($catSlug){
                                $q->where('slug','!=', $catSlug)->select('id');
                            })
                            ->where('is_published',1)
                            ->where('id','!=',$post->id)
                            ->orderBy('id','desc')
                            ->limit(3)
                            ->select('id','title','slug','category_id','description','subcategory_id')
                            ->get();
        User::set_db_connection('');

        // author details
       if(isset($post->author_id))
       {
            $serviceUser = User::where('id', $post->author_id)->where('status',1)->where('is_delete',0)->where('soft_ban',0)->where('vacation_mode', 0)->first();
            
            // top 3 services of author
            $services = Service::where('uid', $post->author_id)
                            ->where('is_custom_order', 0)
                            ->where('is_job', 0)
                            ->where('is_private', 0)
                            ->where('is_delete',0)
                            ->where('status', 'active')
                            ->where('is_approved', 1)
                            ->orderBy('service_rating', 'desc')
                            ->limit(3)
                            ->get();

            //rating system
            $ratingModel = new Order;
            $avg_seller_rating = $ratingModel->calculateSellerAverageRating($post->author_id);
            $total_seller_rating = $ratingModel->getReviewTotal($serviceUser->id);
       }
       else
       {
           $serviceUser = null;
           $services = null;
       }
        return view('frontend.blogs.post_details', compact('post','blog_categories','recent_posts','serviceUser','avg_seller_rating','total_seller_rating', 'services'));
    }
}