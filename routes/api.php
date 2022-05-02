<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::prefix('v1')->namespace('Api\V1')->group(function () {
    Route::post('/register', 'AuthController@register');
    Route::post('/login', 'AuthController@login');
    Route::post('/login-twofactorauth', 'AuthController@login_twofactorauth');
    Route::post('/login-verify-twofactorauth', 'AuthController@login_verify_twofactorauth');
    Route::post('/send-otp', 'AuthController@send_otp');
    Route::post('/verify-otp', 'AuthController@verify_otp');
    Route::post('forgot-password', 'AuthController@forgetPassword');
    
    Route::get('/on-boarding', 'OnboardingController@index');

    Route::group(['middleware' => ['auth:api', 'CheckApiUserStatus']], function () {
        /* Auth : Routes */
        Route::post('/logout', 'AuthController@logout');
        Route::post('/multiple-logout', 'AuthController@multiple_logout');
        Route::get('/get-profile', 'AuthController@getProfile');
        Route::prefix('order')->group(function () {
            Route::post('/buyer-orders', 'OrderController@buyerOrders');
            Route::post('/seller-orders', 'OrderController@sellerOrders');
            Route::post('/show-order', 'OrderController@show_order');
        });

        Route::prefix('chat')->group(function () {
            Route::post('auth', 'MessagesController@pusherAuth');
            Route::post('/notifications', 'MessagesController@notifications');
            Route::post('/delete-notification', 'MessagesController@delete_notification');
            Route::post('/send-message', 'MessagesController@send_message');
            Route::post('/makeSeen', 'MessagesController@seen');
        });

        Route::prefix('service')->group(function () {
            Route::get('/my-services', 'ServiceController@get_my_service_list');
        });
    });
});

Route::prefix('lead/v1')->namespace('Api\demoLead')->group(function () {
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('/service-list', 'demoLeadController@service_list');
    });
});