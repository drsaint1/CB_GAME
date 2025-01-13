<?php

use App\Http\Controllers\Logistics\RegisterController;
use App\Http\Controllers\Logistics\LoginController;
use App\Http\Controllers\Logistics\LogisticsDashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Mail\CompanyOnboard;
use Illuminate\Support\Facades\Crypt;

Route::prefix('logistics')->name('logistics.')->controller(RegisterController::class)->group(function () {
    Route::get('/', 'index')->name('register');
    Route::get('/register', 'create')->name('register-page');
    Route::post('/register', 'store')->name('register');
});

Route::prefix('logistics')->name('logistics.')->controller(LoginController::class)->group(function () {
    Route::get('/create', 'create')->name('login-page');
    Route::post('/create', 'store')->name('login');
    Route::get('/forgot-password', 'forgetPassword')->name('forgot-password-page');
    Route::post('/forgot-password', 'forgetPassword')->name('forgot-password');
});

Route::prefix("logistics")->name('logistics.')->controller(LogisticsDashboardController::class)->group(function() {
    Route::get('/', 'index')->name('dashboard-page');
    Route::get('/dashboard', 'index')->name('dashboard');
});

Route::get("/onboard-logistics/{token}", [RegisterController::class, 'onboard_account'])->name('onboard-logistics');


/** This section is stricly for testing and not to be trusted or used in production */
Route::get("/test", function (){
    return view("logistics/welcome");
});

Route::get('/mailable', function () {
    return new App\Mail\CompanyOnboard("Kobosquare", encrypt("holynationdevelopment@gmail.com"));
});
