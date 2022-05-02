<?php

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  | 
 */

if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
  error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
}

Route::fallback(function () {
  return redirect('404');
});


/**Login cart section  */
Route::POST('login-check', 'Auth\LoginController@loginCheck')->name('login_check');
Route::POST('login-speed', 'Auth\LoginController@speedLogin')->name('speedLogin');

Route::POST('login-cart', 'Auth\LoginController@loginCart')->name('login_cart');
Route::POST('register-login-popup', 'Auth\LoginController@registerLogin')->name('register_login_popup');
Route::POST('forgot-password-popup', 'Auth\LoginController@forgotPasswordPopupView')->name('forgot_password_popup');
Route::POST('login-popup', 'Auth\LoginController@showLoginPopupView')->name('show_login_popup');

/* Verify admin created user */
Route::get('verify-user/{hash}', 'Auth\RegisterController@verify_new_user')->name('user.verify');
Route::match(['get', 'post'], 'create-new-password', 'Auth\RegisterController@change_new_password')->name('user.change_new_password');

Route::POST('register-cart', 'Auth\RegisterController@registerCart')->name('register_cart');
/**Cart page access */
Route::get('cart', 'NewCartController@view_new_cart')->name('view_cart');

/** Cart Page session  */
Route::POST('cart-session', 'NewCartController@cookieCart')->name('cookieCart');

Route::post('cart-session/customize', 'NewCartController@cart_customize_session')->name('cart_customize_session');
Route::post('cart-session/update-cart', 'NewCartController@update_cart_session')->name('update_cart_session');
Route::post('cart-session/update-add-ons', 'NewCartController@update_add_ons_session')->name('update_add_ons_session');
Route::post('cart-session/remove-add-ons', 'NewCartController@remove_add_ons_session')->name('remove_add_ons_session');


Route::group(['middleware' => ['prevent-back-history', 'CheckWallet']], function () {
  Route::get('/', 'HomeController@index')->name('home');
  Route::get('/home', 'HomeController@index')->name('home');
  Auth::routes();
  Route::get('verify_mobile', 'Auth\RegisterController@verify_mobile')->name('verify_register_mobile');
});

Route::get('update-header', 'HomeController@UpdateHeaderData')->name('updateing_header');

Route::get('404', "HomeController@error_404");

Route::get('verify_account/{confirmationCode}', [
  'as' => 'verify_account',
  'uses' => 'FrontEmailController@VerifyAccount'
]);

Route::post('user/checkuserexist', 'HomeController@useralreadyexist');
Route::post('user/checkemailexist', 'HomeController@emailalreadyexist');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');

Route::any('browse/job', 'JobController@browseJob')->name('browse.job');
Route::get('browse/job/search', 'JobController@browseJobSearch')->name('browse.jobSerach');
/*route used for loading dyanamic data in popover*/
Route::get('loadSellerProfile', 'JobController@loadSeller')->name('loadSellerProfile');

Route::get('unsubscribe-from-mail/{secret}', 'MessageController@unsubscribe')->name('unsubscribe');
Route::get('subscribemail-mail/{secret}', 'MessageController@subscribemail')->name('subscribemail');

/* Course List */
Route::get('browse/courses', 'CourseController@courses')->name('courses');
Route::get('courses', 'CourseController@courses');

Route::middleware(['auth', 'prevent-back-history'])->group(function () {
  /* Seller dispute order */

  Route::get('seller-order/dispute-orders-conversations/{dispute_id}', 'SellerController@view_dispute_message')->name('viewSellerDispueMessage'); //Not used

  Route::post('order/dispute-reason', 'BuyerController@get_reasons_for_dispute')->name('get_reasons');
  Route::get('order/dispute-orders-conversations/{secret}', 'BuyerController@view_dispute_message')->name('viewDispueMessage');
  Route::get('order/dispute-orders', 'BuyerController@dispute_order_list')->name('getUserDisputeOrders');
  Route::post('order/create-message/{secret}', 'BuyerController@compose_dispute_message')->name('compose_dispute_message');

  /*Paypal Deposit wallet*/
  Route::post('paypal/deposit/express/checkout', 'PaypalPaymentController@expressCheckoutDeposit')->name('paypal_express_checkout_deposit');
  Route::get('paypal/deposit/express/success', 'PaypalPaymentController@expressCheckoutDepositSuccess')->name('paypal_express_checkout_deposit_success');

  /* Add money to wallet from paypal*/
  Route::post('paypal/deposit/add_money_to_wallet', 'PaypalPaymentController@expressCheckoutAddMoney')->name('paypal_add_money_to_wallet');
  Route::get('paypal/deposit/add_money_to_wallet_success', 'PaypalPaymentController@expressCheckoutAddMoneySuccess')->name('add_money_to_wallet_success');

  /* Add money to wallet from credit card*/
  Route::get('bluesnap/deposit_to_wallet/thankyou/{invoice_id}', 'PaypalPaymentController@ccDepositeAmtThankyou')->name('cc_deposite_amt.thankyou');
  Route::get('bluesnap/deposit/check_payment', 'PaypalPaymentController@ccDepositeAmtCheckPayment')->name('cc_deposite_amt.check_payment');
  Route::post('bluesnap/deposit/refund', 'PaypalPaymentController@ccDepositeAmtRefund')->name('cc_deposite_amt.refund');


  /* apply hidden pizza */
  Route::post('apply-hidden-pizza', 'HomeController@apply_hidden_pizza')->name('apply_hidden_pizza');
  Route::post('can_show_pizza_for_category', 'HomeController@can_show_pizza_for_category')->name('can_show_pizza_for_category');

  /* new notification */
  Route::post('notification/mark-as-read', 'HomeController@notification_mark_as_read')->name('notification_mark_as_read');
  Route::post('notification/clear', 'HomeController@notification_clear')->name('notification_clear');
  Route::post('notification/all/mark-as-read', 'HomeController@all_notification_mark_as_read')->name('all_notification_mark_as_read');
  Route::post('notification/all/clear', 'HomeController@all_notification_clear')->name('all_notification_clear');
});

Route::middleware(['auth', 'prevent-back-history', 'CheckWallet'])->group(function () {
  Route::post('resendemail', 'HomeController@ResendEmail')->name('resendemail');
  Route::post('accept_tearms', 'HomeController@accept_tearms')->name('accept_tearms');
  Route::post('submit_new_features', 'HomeController@submit_new_features')->name('submit_new_features');
  Route::post('update_confetti_effect', 'HomeController@update_confetti_effect')->name('update_confetti_effect');

  Route::get('accountsetting', 'SellerController@account')->name('accountsetting');
  Route::post('accountsetting', 'SellerController@account')->name('accountsetting');
  Route::post('seller/profileImage', 'SellerController@profileImage')->name('profileImage');

  Route::get('seller/security', 'SellerController@security')->name('security');
  Route::post('seller/security', 'SellerController@security')->name('security');

  Route::get('service', 'ServiceController@index')->name('services');

  /**Service Gallery reorder */
  Route::post('service/gallery-reorder', 'ServiceController@galleryReorder')->name('galleryReorder');

  /* service FAQ */
  Route::get('service/faq/{seo_url}', 'FAQController@get_faq')->name('get_faq');
  Route::get('service/faq/add/{seo_url}', 'FAQController@add_faq')->name('add_faq');
  Route::get('service/faq/edit/{id}', 'FAQController@edit_faq')->name('edit_faq');
  Route::post('service/faq/save', 'FAQController@save_faq')->name('save_faq');
  Route::post('service/faq/edit/save', 'FAQController@update_faq')->name('update_faq');
  Route::get('service/faq/delete/{id}', 'FAQController@delete_faq')->name('delete_faq');
  Route::post('service/faq/details', 'FAQController@get_faq_details')->name('get_faq_details');

  /*Offer volume discounts for services and courses*/
  Route::get('offer/volume/discount/{service_id}', 'OfferVolumeDiscountController@index')->name('offer_volume_discount');
  Route::post('offer/volume/store', 'OfferVolumeDiscountController@store')->name('store_volume_discount');
  Route::post('offer/volume/check_exists', 'OfferVolumeDiscountController@checkExists')->name('no_of_volume_service');
  Route::get('offer/volume/delete/{id}', 'OfferVolumeDiscountController@delete')->name('delete_volume_service');

  /*Offer Bundle Discount for services*/
  Route::get('offer/bundle/discount', 'OfferBundleDiscountController@index')->name('offer_bundle_discount');
  Route::get('offer/bundle/delete/{id}', 'OfferBundleDiscountController@delete')->name('delete_bundle_discount');
  Route::get('offer/bundle/create', 'OfferBundleDiscountController@create')->name('create_bundle_offer');
  Route::post('offer/bundle/store', 'OfferBundleDiscountController@store')->name('store_bundle_offer');
  Route::get('offer/bundle/edit/{id}', 'OfferBundleDiscountController@edit')->name('edit_bundle_offer');
  Route::post('offer/bundle/update', 'OfferBundleDiscountController@update')->name('update_bundle_offer');
  Route::post('offer/bundle/check_discount', 'OfferBundleDiscountController@checkDiscount')->name('check_bundle_discount');

  /*Offer Bundle Discount for courses*/
  Route::get('course/bundle/discount', 'CourseBundleDiscountController@index')->name('course.offer_bundle_discount');
  Route::get('course/bundle/delete/{id}', 'CourseBundleDiscountController@delete')->name('course.delete_bundle_discount');
  Route::get('course/bundle/create', 'CourseBundleDiscountController@create')->name('course.create_bundle_offer');
  Route::post('course/bundle/store', 'CourseBundleDiscountController@store')->name('course.store_bundle_offer');
  Route::get('course/bundle/edit/{id}', 'CourseBundleDiscountController@edit')->name('course.edit_bundle_offer');
  Route::post('course/bundle/update', 'CourseBundleDiscountController@update')->name('course.update_bundle_offer');
  Route::post('course/bundle/check_discount', 'CourseBundleDiscountController@checkDiscount')->name('course.check_bundle_discount');

  /*boost/sponsered  service*/
  Route::get('service/boost/{seo_url}', 'ServiceController@boostTheService')->name('boostService');
  Route::post('service/boost/payment', 'ServiceController@boostPayment')->name('boostPayment');
  Route::post('service/boost/payment/options', 'ServiceController@boost_cart_payment_options')->name('boost_cart_payment_options');
  Route::post('service/boost/rent-section', 'ServiceController@rentAdSpot')->name('rentAdSpot');
  Route::get('seller/orders/sponsered/details/{id}', 'SellerController@sponsered_order_derails')->name('seller_sponsered_orders_details');

  /* Coupan */
  Route::get('service/coupan/{id}/{type?}', 'CoupanController@index')->name('coupan');
  Route::get('service/addCoupan/{id}/{type?}', 'CoupanController@showAddCoupan')->name('showAddCoupan');
  Route::post('service/coupan', 'CoupanController@coupanSubmit')->name('coupanSubmit');
  Route::get('service/editCoupan/{id}/{type}', 'CoupanController@showcoupanEdit')->name('showcoupanEdit');
  Route::post('service/editCoupan/{id}/{type}', 'CoupanController@submitCoupanEdit')->name('coupaneditSubmit');
  Route::get('service/removeCoupan', 'CoupanController@coupanDelete')->name('coupanDelete');
  Route::post('service/promotion', 'CoupanController@updatePromotionStatus')->name('updatePromotionStatus');

  /* seller's general coupons */
  Route::get('seller/general-coupon', 'CoupanController@seller_coupons')->name('seller_coupons');
  Route::get('seller/general-coupon/add', 'CoupanController@add_general_coupon')->name('add_general_coupon');
  Route::post('seller/general-coupon/add/save', 'CoupanController@save_add_general_coupon')->name('save_add_general_coupon');
  Route::get('seller/general-coupon/edit/{id}', 'CoupanController@edit_general_coupon')->name('edit_general_coupon');
  Route::post('seller/general-coupon/edit/save', 'CoupanController@save_edit_general_coupon')->name('save_edit_general_coupon');

  /* Buyer and Seller :: Dropzone */
  Route::post("buyer/upload_files", 'BuyerController@upload_files')->name('upload_files');
  Route::post("buyer/upload_files_s3", 'BuyerController@upload_files_s3')->name('upload_files_s3');
  Route::post("buyer/upload_temp_file", 'BuyerController@upload_temp_file')->name('upload_temp_file');
  Route::get('buyer/getallfiles', 'BuyerController@getallfiles')->name('getallfiles');
  Route::post('buyer/removefile', 'BuyerController@removefile')->name('removefile');
  Route::get('download-file/{id}', 'BuyerController@download_files')->name('download_files');
  Route::get('download/s3/file', 'BuyerController@download_files_s3')->name('download_files_s3');
  Route::get('download/all/files/{order_id}', 'BuyerController@donwload_all_media')->name('download.all.files');

  /*Browse Jobs*/
  Route::get('show/job_detail/{seo}', 'JobController@showJobDetail')->name('show.job_detail');
  Route::post('send/job_proposal', 'JobController@storeJobProposal')->name('send.job_proposal');
  Route::get('dyanamic/job_div', 'JobController@showEditForm')->name('dyanamic.job_div');
  Route::get('delete/proposal', 'JobController@destroyProposal')->name('delete.proposal');
  Route::get('accept/proposal', 'JobController@acceptProposal')->name('accept.proposal');
  Route::get('reject/proposal_seller', 'JobController@rejectProposalSeller')->name('reject.proposal_seller');
  Route::get('accept/proposal_seller', 'JobController@acceptProposalSeller')->name('accept.proposal_seller');
  Route::get('paypal/job/promote-bid/express/success', 'JobController@expressCheckoutPromoteBidSuccess')->name('paypal_express_checkout_promote_bid_success');
  Route::post('job/offer/rating', 'JobController@update_job_bid_rating')->name('update_job_bid_rating');
  Route::get('job/offer/hide/{secret}', 'JobController@hide_job_bid')->name('hide_job_bid');


  /* Messages */
  Route::post('message/msg_compose/{type}/{slug}/{job_offer_by?}', 'MessageController@messageCompose')->name('message_compose');
  Route::post('message/msg_reply/{secret}', 'MessageController@reply')->name('msg_reply');
  Route::post('message/msg_reply/attachment/{secret}', 'MessageController@msg_attachment')->name('msg_attachment');

  //Route::get('message/conversations', 'MessageController@conversations')->name('msg_conversations');
  Route::get('message/details/{secret}', 'MessageController@details')->name('msg_details');

  Route::get('message/canned-reply', 'MessageController@cannnedReplay')->name('canned_replay');
  Route::get('message/sent', 'MessageController@sent')->name('msg_sent');
  Route::get('message/archived', 'MessageController@archived')->name('msg_archived');

  Route::post('message/spamReport/{secret}', 'MessageController@spamReport')->name('spamReport');

  /*Predifine template*/
  Route::post('seller/select_template', 'ServiceController@select_template');
  Route::post('seller/update_template', 'ServiceController@update_template');
  Route::post('seller/delete_template', 'ServiceController@delete_template');

  /*User Follower*/
  Route::post('follower', 'UserFollowController@store')->name('follower');

  Route::post('buyer/request-custom-quote', 'BuyerController@request_custom_quote')->name('request_custom_quote');

  Route::middleware(['checkSubuserPermission'])->group(function () {
    /*favorite_services*/
    Route::post("services/favorite", 'FavoriteServiceController@favorite');
    Route::get("services/favorite", 'FavoriteServiceController@index')->name("favorites");

    /* Seller */
    Route::get('seller/sellerProfile', 'SellerController@sellerProfile')->name('seller_profile');
    Route::post('seller/sellerProfile', 'SellerController@sellerProfile')->name('seller_profile');
    Route::get('seller/billing', 'SellerController@billing')->name('billing');
    Route::post('seller/billing', 'SellerController@billing')->name('billing');

    Route::get('seller/affiliate', 'SellerController@affiliate')->name('affiliate');
    Route::post('seller/affiliate', 'SellerController@affiliate')->name('affiliate');

    /* Upload introduction video */
    Route::post('seller/upload-introduction-video', 'SellerController@uploadIntroductionVideo')->name('upload.introduction.video');
    Route::delete('seller/delete-introduction-video/{type}/{secret}', 'SellerController@deleteIntroVideo')->name('delete.introduction.video');
    Route::post('seller/intro-video-thumbnail/resize', 'SellerController@introVideoThumbnailResize')->name('resize.video.thumbnail');

    /*Mail settings*/
    Route::any('seller/mail-settings', 'SellerController@mailSettings')->name('mail_settings');

    /*Paypal service payment*/
    Route::get('paypal/express/success', 'PaypalPaymentController@expressCheckoutSuccess')->name('paypal_express_checkout_success');

    /*Boost payment skrill*/
    Route::post('skrill/boostservice/payment', 'SkrillPaymentController@bootServicePayment')->name('skrill.boost.checkout');

    /*Paypal Premium seller*/
    Route::any('paypal/premium/express/checkout', 'PaypalPaymentController@expressCheckoutPremium')->name('paypal_express_checkout_premium');
    Route::get('paypal/premium/express/success', 'PaypalPaymentController@expressCheckoutPremiumSuccess')->name('paypal_express_checkout_premium_success');
    Route::get('cancel/premium_subscription', 'PaypalPaymentController@cancelPremiumSubscription')->name('cancel_premium_subscription');

    /*Seller earning/withdraw request*/
    Route::get('seller/earning', 'SellerController@earning')->name('earning');
    Route::get('seller/reports', 'SellerController@earning_report')->name('earning_report');

    /* Buyer */
    Route::get('buyer/orders', 'BuyerController@orders')->name('buyer_orders');
    Route::get('buyer/orders/details/{id}', 'BuyerController@order_derails')->name('buyer_orders_details');
    Route::get('buyer/my-courses', 'BuyerController@myCourses')->name('buyer.mycourses');

    Route::get('become/premium-seller', 'HomeController@becomePremiumSeller')->name('become_premium_seller');
    Route::get('my-premium/subscription', 'HomeController@myPremiumSubscription')->name('my_premium_subscription');
    Route::get('premium-seller/payment', 'HomeController@show_premium_payment')->name('show_premium_payment');
    Route::get('premium-seller/payment-options', 'HomeController@show_premium_payment_options')->name('show_premium_payment_options');
  });

  //Affiliate offers routes
  Route::get('affiliate-offers', 'AffiliateController@AffiliateOffers')->name('affiliate_offers');
  Route::get('affiliates/filter', 'AffiliateController@AffiliateOffersFilter')->name('AffiliateOffersFilter');

  /* buyer's order tag */
  Route::get('seller/order-tags', 'OrderTagController@order_tags')->name('order_tags');
  Route::post('buyer/order-tags/add/save', 'OrderTagController@save_add_order_tag')->name('save_add_order_tag');
  Route::post('buyer/order-tags/edit/save', 'OrderTagController@save_edit_order_tag')->name('save_edit_order_tag');
  Route::post('buyer/order-tags/delete', 'OrderTagController@delete_order_tag')->name('delete_order_tag');
  Route::post('buyer/orders/tags', 'OrderTagController@get_tags_list')->name('get_tags_list');
  Route::post('buyer/order-tags/clear-orders', 'OrderTagController@clear_orders_from_tag')->name('clear_orders_from_tag');
  Route::post('buyer/order-tags/add-into-tag', 'OrderTagController@add_order_into_tag')->name('add_order_into_tag');
  Route::post('buyer/order-tags/remove-tag', 'OrderTagController@remove_tag_from_order')->name('remove_tag_from_order');
  Route::post('buyer/order-tags/clear-all-tags', 'OrderTagController@clear_all_tags_from_order')->name('clear_all_tags_from_order');

  /* User followers */
  Route::get('{username}/followers', 'UserFollowController@followers')->name('user.followers');
  /* User following */
  Route::get('{username}/following', 'UserFollowController@followings')->name('user.followings');


  /* Courses Routes */
  Route::get('mycourses', 'CourseController@index')->name('mycourses');
  Route::match(['get', 'post'], 'course/overview', 'CourseController@overview')->name('course.overview');
  Route::match(['get', 'post'], 'course/overview/{seo}', 'CourseController@update_overview')->name('course.update_overview');
  Route::match(['get', 'post'], 'course/description/{seo}', 'CourseController@description')->name('course.description');
  Route::match(['get', 'post'], 'course/requirement/{seo}', 'CourseController@requirement')->name('course.requirement');
  Route::match(['get', 'post'], 'course/publish/{seo}', 'CourseController@publish')->name('course.publish');
  Route::get('course/remove/{seo_url}', 'CourseController@remove')->name('course.remove');

  //Course Section Routes
  Route::match(['get', 'post'], 'course/section/{seo}', 'CourseController@section')->name('course.section');
  Route::post('course/section/create/{seo}', 'CourseController@create_section')->name('course.section.create');
  Route::delete('course/section/destroy/{seo}/{secret}', 'CourseController@delete_section')->name('course.section.delete');
  Route::post('course/section/change/ordering', 'CourseController@change_ordering_section')->name('course.section.change_ordering');

  /* Begin: Unsubscribe Email */
  Route::match(['get', 'post'], 'unsubscribe-email/{token}', 'FrontEmailController@unsubscribeEmail')->name('unsubscribe.email');
});


/*Deposit amount*/
Route::get('update-wallet', 'SellerController@update_wallet')->name('update_wallet');

Route::post('order/dispute-order/{id}', 'BuyerController@dispute_order')->name('dispute_order');
Route::post('order/cancel-dispute/{id}', 'BuyerController@cancel_dispute')->name('cancel_dispute');

/* notification route of aimtell */
Route::post('store-subscriber-id', 'HomeController@store_subscriber_id')->name('store_subscriber_id');

/* Portfolio Routes */
Route::match(['get', 'post'], 'portfolio/create', 'PortfolioController@create')->name('portfolio.create');
Route::get('{username}/portfolio/', 'PortfolioController@index')->name('portfolio');
Route::match(['get', 'post'], 'portfolio/update/{id}', 'PortfolioController@update')->name('portfolio.update');
Route::get('portfolio/delete/{id}', 'PortfolioController@delete')->name('portfolio.delete');
Route::post('portfolio/media/upload', 'PortfolioController@upload_media')->name('portfolio.mediaupload');
Route::post('portfolio/delete/temp-media', 'PortfolioController@delete_temp_media')->name('delete.tempmedia');
Route::post('portfolio/change/ordering', 'PortfolioController@change_ordering')->name('portfolio.change_ordering');

/*Display User profile*/
Route::get('profile/{username}', 'ServiceController@viewUserServices'); /*use for old url*/
Route::get('{username}', 'ServiceController@viewUserServices')->name('viewuserservices');

Route::get('profile/{username}/{seo_url}', 'ServiceController@details'); /*Old route*/
Route::get('course/{username}/{seo_url}', 'CourseController@details')->name('course_details'); /* Course Details Route */
Route::get('{username}/{seo_url}', 'ServiceController@details')->name('services_details');

/* upgrade order routes */
Route::post('order/upgrade/payment', 'BuyerController@upgrade_order_payment')->name('upgrade_order_payment');
Route::post('order/upgrade/payment/options', 'BuyerController@upgrade_order_payment_options')->name('upgrade_order_payment_options');
Route::post('paypal/upgrade/order/walletPay', 'PaypalPaymentController@walletPayUpgradeOrder')->name('walletPayUpgradeOrder');
Route::post('paypal/upgrade/order/express/checkout', 'PaypalPaymentController@expressCheckoutUpgradeOrder')->name('paypal_express_checkout_upgrade_order');
Route::get('paypal/upgrade/order/express/success', 'PaypalPaymentController@expressCheckoutUpgradeOrderSuccess')->name('paypal_express_checkout_upgrade_order_success');
/*upgrade order bluesnap*/
Route::post('bluesnap/upgrade/order/payment', 'BluesnapPaymentController@bluesnapUpgradeOrderPayment')->name('bluesnapUpgradeOrderPayment');
Route::get('bluesnap/upgrade/order/thankyou/{invoice_id}', 'BluesnapPaymentController@blueSnapUpgradeOrderThankyou')->name('bluesnap.upgradeorder.thankyou');
Route::get('bluesnap/upgrade/order/checkpayment', 'BluesnapPaymentController@blueSnapUpgradeOrderCheckpayment')->name('bluesnap.upgradeorder.checkpayment');
