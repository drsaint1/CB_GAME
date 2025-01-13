<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserWallet;
use App\Services\KoboService;
use App\Models\TableEntity;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\KoboNotification;
use App\Services\PushNotification;

class WalletController extends BaseController
{
    private $kobo;
    private $table;
    private $notice;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
        $this->table = new TableEntity();
        $this->notice = new PushNotification();
    }

    public function getWallet()
    {
        $users = Auth::guard('api')->user()->id;
        $wallet = UserWallet::where('user_id', $users)->first();
        if (isset($wallet)) {
            return $this->sendResponse($wallet, 'User Wallet Balance');
        } else {
            return $this->sendError('Error', 'oOps, Somwething went wrong!!!');
        }
    }

    public function payKoboUser(Request $request)
    {
        $auth = Auth::guard('api')->user();
        if (!$request->pin) {
            $validator = Validator::make($request->all(), [
                'to' => 'required',
                'amount' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            } else {
                $user = DB::table('users')->where('username', $request->to)->first();
                if (isset($user->name)) {
                    $data = [
                        "recipient_id" => $request->to,
                        "name" => $user->name,
                        "amount" => number_format($request->amount, 2)
                    ];
                    return $this->sendResponse($data, "Payment Preview");
                } else {
                    return $this->sendError('Error', 'User not found');
                }
            }
        }
        if ($request->pin && Hash::check($request->pin, $auth->pin)) {
            if (isset($auth->bvn)) {
                $user = DB::table('users')->where('username', $request->to)->first();
                $bal = DB::table('user_wallets')->where('user_id', $auth->id)->first();
                $userBal = DB::table('user_wallets')->where('user_id', $user->id)->first();
                if ((float) $bal->balance < number_format($request->amount, 2)) {

                    $failedmsg = "Your available balance is insufficient to perform this transaction. Fund your wallet to proceed.";
                    Notification::send($auth, new KoboNotification(['title' => 'Transaction failed', 'message' => $failedmsg]));

                    return $this->sendError('Insufficient balance', 'Your available balance is insufficient to perform this transaction. Fund your wallet to proceed.');
                } else if ($request->to == $auth->username) {
                    return $this->sendError('Warning', 'oOps, You can\'t transfer to yourself');
                } else {
                    DB::table('user_wallets')->where('user_id', $auth->id)->update(['balance' => $bal->balance - $request->amount]);
                    DB::table('user_wallets')->where('user_id', $user->id)->update(['balance' =>  $userBal->balance + $request->amount]);

                    // Log Transaction
                    $trans_ref = $this->kobo->generateTransRef();

                    //  dd($user);

                    $dtat = [
                        'user_id' => $auth->id,
                        'trans_ref' => $trans_ref,
                        'mac_address' => $request->mac_address,
                        'ip_address' => $request->ip_address,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'type' => 'Local Fund Transfer',
                        'method' => "transfer",
                        "status" => "Successful",
                        'amount' => $request->amount,
                        'from' => $auth->id,
                        'to' => $user->username
                    ];
                    $this->table->insertNewEntry('transactions', 'id', $dtat);


                    if (isset($auth->email) && $auth->emailVerified == 1) {
                        $msg = "Transaction Successful, You just paid ' . $request->to . ' the sum of NGN " . number_format($request->amount, 2);
                        $subject = "Kobosquare Successful Transaction";
                        $this->kobo->anyEmailNotification($auth->email, $auth->name, $msg, $subject);
                    }

                    if (isset($user->email) && $user->emailVerified == 1) {
                        $msg = "Payment notification from, ' . $auth->name . 'sent you the sum of NGN " . number_format($request->amount, 2);
                        $subject = "Kobosquare Recieved Payment";
                        $this->kobo->anyEmailNotification($user->email, $user->name, $msg, $subject);
                    }


                    $authmsg = "Transaction Successful, You just paid ' . $request->to . ' the sum of NGN " . number_format($request->amount, 2);
                    Notification::send($auth, new KoboNotification(['title' => 'Transaction Successful', 'message' => $authmsg]));

                    $usermsg ="Payment notification from, ' . $auth->name . 'sent you the sum of NGN " . number_format($request->amount, 2);


                    Notification::send($user, new KoboNotification(['title' => 'Payment Received', 'message' => $usermsg]));

                    $this->notice->sendPushNotification("01", 'Transaction Successful', 'Hello, ' . $auth->username . ', Transaction Successful. You just paid ' . $request->to . ' the sum of NGN ' . number_format($request->amount, 2), array($auth->token), null, $dtat);

                    $this->notice->sendPushNotification("01", 'Payment Received', 'Hello, ' . $user->username . ', ' . $auth->username . ' sent you the sum of NGN ' . number_format($request->amount, 2), array($user->token), null, $dtat);




                    return $this->sendResponse('Transaction Successful', 'You just paid ' . $request->to . ' the sum of NGN' . number_format($request->amount, 2));

                }
            } else {
                return $this->sendError('Transaction failed', 'Please verify your bvn to complete transaction');
            }
        } else {
            return $this->sendError('Transaction failed', 'Your pin is incorrect');
        }
    }

    public function GetTransaction(){
        $user = Auth::guard('api')->user();
        $transactions = Transaction::with(['user', 'sub_product', 'sub_product.product'])->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        return $this->sendResponse($transactions, 'User Transaction History');
    }

    public function filterUserTransactionHistory($status){
        $user = Auth::guard('api')->user();
        $history = Transaction::where('user_id', $user->id)->with(['user', 'sub_product', 'sub_product.product'])->where('status', $status)->orderBy('created_at', 'desc')->get();
        return $this->sendResponse($history,  $status."transaction histories");
    }



    public function TransactionInfo($trans_ref)
    {
        $auth = Auth::guard('api')->user();
        $data = Transaction::with(['user', 'sub_product', 'sub_product.product'])->where('trans_ref', $trans_ref)->first();
        // if ($trx->from == $auth->id) {
        //     $from = $auth->name;
        //     $to = DB::table('users')->where('id', $trx->to)->first()->name;
        //     $amount = '-₦' . $trx->amount;
        //     $data = [
        //         "Type" => $trx->type,
        //         "Method" => $trx->method,
        //         "Reference" => $trx->trans_ref,
        //         "Status" => $trx->type,
        //         "amount" => $amount,
        //         "DateTime" => $trx->created_at,
        //         "Beneficiary" => $to
        //     ];
        // }

        // if ($trx->from == 'Wallet') {
        //     $from = $auth->name;
        //     $amount = '-₦' . $trx->amount;
        //     $data = [
        //         "Type" => $trx->type,
        //         "Method" => $trx->method,
        //         "Reference" => $trx->trans_ref,
        //         "Status" => $trx->type,
        //         "amount" => $amount,
        //         "DateTime" => $trx->created_at,
        //         "Beneficiary" => $trx->to
        //     ];
        // }

        // if ($trx->to == $auth->id) {
        //     $from = DB::table('users')->where('id', $trx->from)->first()->name;
        //     $to = $auth->name;
        //     $amount = '₦' . $trx->amount;
        //     $data = [
        //         "Type" => $trx->type,
        //         "Method" => $trx->method,
        //         "Reference" => $trx->trans_ref,
        //         "Status" => $trx->type,
        //         "amount" => $amount,
        //         "DateTime" => $trx->created_at,
        //         "From" => $from
        //     ];
        // }
        return $this->sendResponse($data, 'Transaction Details');
    }
}
