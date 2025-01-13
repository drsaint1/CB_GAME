<?php

// use App\Http\Controllers\Api\AuthController;
// use App\Http\Controllers\Api\BankController;
// use App\Http\Controllers\Api\BillPaymentController;
// use App\Http\Controllers\Api\BookingController
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
use App\Models\Role;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



// Public Routes
Route::post('/connect-wallet', [UserController::class, 'connectWallet']);
Route::get('/getUserDetails', [UserController::class, 'getUserDetails']);
Route::post('/setUsername', [UserController::class, 'setUsername']);

Route::post('/wallet/store', [WalletController::class, 'store']); // Store wallet address
Route::get('/leaderboard/{walletAddress}', [LeaderboardController::class, 'index']); // Fetch leaderboard rankings

// Route::get('/referral/{walletAddress}', [ReferralController::class, 'register']);
Route::post('/register', [ReferralController::class, 'register']);
Route::get('/wallet-referrals/{walletAddress}', [ReferralController::class, 'getReferrals']);



Route::post('/save-points', [GameController::class, 'savePoints']);
Route::post('/log-transaction', [GameController::class, 'logTransaction']);


Route::get('/wallet', [WalletController::class, 'index']); // Fetch wallet details
Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']); // Withdraw points
Route::post('/withdraw', [WalletController::class, 'withdrawPoints']);


Route::get('/transactions', [TransactionController::class, 'index']); // Fetch transactions



