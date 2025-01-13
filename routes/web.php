<?php
use App\Http\Controllers\Driver\DriverAuthController;
use App\Http\Controllers\Webhook\CallBackController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function() {
    return view('welcome');
});

Route::get("/order", function() {
    return view('emails.order');
});


Route::get("/email", function() {
    return view('emails.email');
});

Route::get("/promote", function() {
    return view('emails.promote');
});

Route::get("/success", function() {
    return view('emails.success');
});

Route::get("/otp", function() {
    return view('emails.otp-verify');
});

// Route::get('/linkstorage', function () {
//     Artisan::call('storage:link');
//     return 'Storage link has been created.';
// });



Route::post('payment/notification/webhook', [CallBackController::class, 'paymentNotification']);

Route::post('/transfer/webhook', [CallBackController::class, 'failedWithdrawal']);

//require __DIR__."/logistics.php";
