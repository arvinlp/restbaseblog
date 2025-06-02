<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        "name" => "Base RestApi Blog System",
        "version" => "1.0.0",
    ], 200);
})->name('main');
// Authentication Routes
Route::group(['prefix' => 'auth', 'as' => 'auth.', 'middleware' => 'throttle:3,1'], function () {
    Route::post('/send-code', 'AuthController@sendVerificationCode')->name('send.code')->middleware('guest');
    Route::post('/verify-code', 'AuthController@verifyCode')->name('verify.code')->middleware('guest');
    Route::post('/register', 'AuthController@register')->name('register')->middleware('guest');
    // Login and Logout Routes
    Route::post('/login', 'AuthController@login')->name('login')->middleware('guest');
    Route::post('/logout', 'AuthController@logout')->name('logout')->middleware('auth');
    // Password Reset Routes
    Route::post('/password/reset', 'AuthController@resetPassword')->name('password.reset')->middleware('guest');
    Route::post('/password/reset/verify', 'AuthController@verifyResetCode')->name('password.reset.verify')->middleware('guest');
    Route::post('/password/reset/change', 'AuthController@changePassword')->name('password.change')->middleware('guest');
});
// User Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', 'AuthController@getUser')->name('user');
    Route::put('/user', 'AuthController@updateUser')->name('user.update');
});
// Public Routes
Route::get('/posts', 'PostController@index')->name('posts.list');
Route::get('/posts/{id}', 'PostController@show')->name('posts.show');
Route::get('/categories', 'CategoryController@index')->name('categories.list');
Route::get('/categories/{id}', 'CategoryController@show')->name('categories.show');
// Admin Routes for posts
Route::group(['prefix' => 'posts', 'as' => 'posts.', 'middleware' => ['auth:sanctum', 'throttle:3,1']], function () {
    Route::get('/list', 'PostController@list')->name('all');
    Route::get('/trash', 'PostController@trash')->name('trash');
    Route::post('/new', 'PostController@create')->name('create');
    Route::put('/{id}', 'PostController@update')->name('update');
    Route::delete('/{id}', 'PostController@delete')->name('delete');
    Route::delete('/{id}/force', 'PostController@forceDelete')->name('force.delete');
});
// Admin Routes for categories
Route::group(['prefix' => 'categories', 'as' => 'categories.', 'middleware' => ['auth:sanctum', 'throttle:3,1']], function () {
    Route::get('/list', 'CategoryController@list')->name('all');
    Route::get('/trash', 'CategoryController@trashed')->name('trash');
    Route::get('/trash/{id}', 'CategoryController@trashed')->name('trash.show');
    Route::post('/new', 'CategoryController@create')->name('create');
    Route::put('/{id}', 'CategoryController@update')->name('update');
    Route::delete('/{id}', 'CategoryController@delete')->name('delete');
    Route::delete('/{id}/force', 'CategoryController@forceDelete')->name('force.delete');
});
// Admin Routes for users
Route::group(['prefix' => 'users', 'as' => 'users.', 'middleware' => ['auth:sanctum', 'throttle:3,1']], function () {
    Route::get('/', 'UserController@index')->name('list');
    Route::get('/{id}', 'UserController@show')->name('show');
    Route::post('/new', 'UserController@create')->name('create');
    Route::put('/{id}', 'UserController@update')->name('update');
    Route::delete('/{id}', 'UserController@delete')->name('delete');
});
