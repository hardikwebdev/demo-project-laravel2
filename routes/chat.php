<?php
/**
 * -----------------------------------------------------------------
 * NOTE : There is two routes has a name (user & group),
 * any change in these two route's name may cause an issue
 * if not modified in all places that used in (e.g Chatify class,
 * Controllers, chatify javascript file...).
 * -----------------------------------------------------------------
 */

/*
* This is the main app route [Chatify Messenger]
*/
//Route::get('/', 'MessagesController@index')->name(config('chatify.path'));
//Route::get('/messages', 'MessagesController@messages');

/**
 *  Fetch info for specific id [user/group]
 */
Route::post('/idInfo', 'MessagesController@idFetchData');

/**
 * Send message route
 */
Route::post('/sendMessage', 'MessagesController@send')->name('send.message');

/**
 * Create new conversation route
 */
Route::post('/createNewConversation', 'MessagesController@createNewConversation')->name('create.conversation');

/**
 * Fetch messages 
 */
Route::post('/fetchMessages', 'MessagesController@fetch')->name('fetch.messages');

/**
 * Download attachments route to create a downloadable links
 */
Route::get('/download/{fileName}', 'MessagesController@download')->name(config('chatify.attachments.route'));

/**
 * Authintication for pusher private channels
 */
Route::post('/chat/auth', 'MessagesController@pusherAuth')->name('pusher.auth');

/**
 * Make messages as seen
 */
Route::post('/makeSeen', 'MessagesController@seen')->name('messages.seen');

/**
 * Make messages as unseen
 */
Route::post('/makeUnseen', 'MessagesController@unSeen')->name('messages.unseen');


/**
 * Get contacts
 */
Route::post('/getContacts', 'MessagesController@getContacts')->name('contacts.get');

/**
 * Update contact item data
 */
Route::post('/updateContacts', 'MessagesController@updateContactItem')->name('contacts.update');


/**
 * Star in favorite list
 */
Route::post('/star', 'MessagesController@favorite')->name('star');

/**
 * get favorites list
 */
Route::post('/favorites', 'MessagesController@getFavorites')->name('favorites');

/**
 * Search in messenger
 */
Route::post('/search', 'MessagesController@search')->name('search');

/**
 * Get shared photos
 */
Route::post('/shared', 'MessagesController@sharedPhotos')->name('shared');

/**
 * Delete Conversation
 */
Route::post('/deleteConversation', 'MessagesController@deleteConversation')->name('conversation.delete');

/**
 * Delete Conversation
 */
Route::post('/updateSettings', 'MessagesController@updateSettings')->name('avatar.update');

/**
 * Set active status
 */
Route::post('/setActiveStatus', 'MessagesController@setActiveStatus')->name('activeStatus.set');






/*
* [Group] view by id
*/
Route::get('/group/{id}', 'MessagesController@index')->name('group');

/*
* user view by id.
* Note : If you added routes after the [User] which is the below one,
* it will considered as user id.
*
* e.g. - The commented routes below :
*/
// Route::get('/route', function(){ return 'Munaf'; }); // works as a route
Route::middleware(['auth','prevent-back-history'])->group(function () {
    Route::get('/{id}', 'MessagesController@index')->name('users');
});
// Route::get('/route', function(){ return 'Munaf'; }); // works as a user id