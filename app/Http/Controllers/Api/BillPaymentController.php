<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\BillEntity;
use App\Models\BillSubEntity;
use App\Services\RingoService;
use App\Services\KoboService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\KoboNotification;
use App\Services\PushNotification;

class BillPaymentController extends BaseController
{
    private $ringo;
    private $kobo;
    private $notice;

    public function __construct(RingoService $ringo, KoboService $kobo)
    {
        $this->ringo = $ringo;
        $this->kobo = $kobo;
        $this->notice = new PushNotification();
    }

    public function postTransaction(Request $request){
        $inputs = $request;
        $auth = Auth::guard('api')->user();
        $clients = Auth::guard('api')->user()->id;
        $subProduct = $this->ringo->getSubProductDetail($inputs->sub_bill_id);
        $bill = $this->ringo->getProductBySubId($subProduct->bill_entity_id);
        if($bill->type == 'data' || $bill->type == 'airtime'){
            $amount = (int)$inputs['amount'];
        }
        else{
            $amount = (int)$inputs['amount'] + 100;
        }
        if ($amount > 0) {
            $accountBal = $this->ringo->getWalletBalance($clients);
            if(isset($auth->bvn)){
                if ($request->pin && Hash::check($request->pin, $auth->pin)) {
                    if((int)$accountBal->balance >= $amount){
                        $transaction = $this->ringo->postTransactionBill($inputs, $clients, $amount);
                        if ($transaction != null) {
                            //$this->mproxy->sendPostedTransNotifications($transaction, 'New Purchase Transaction');
                            $this->ringo->billPaymentAutomation($transaction);

                            if(isset($auth->email)){
                                $msg="Your purchase of " . $transaction['type'] . " from KoboSquare has been submitted successfully!";
                                $subject="koboSquare Successfull Transaction ";
                                $this->kobo->anyEmailNotification($auth->email,$auth->name,$msg,$subject);
                            }

                            $authmsg = "Transaction Successful, Your purchase of " . $transaction['type'] . " from KoboSquare has been submitted successfully! ";
                            Notification::send($auth, new KoboNotification(['title' => 'Transaction Successful', 'message' => $authmsg]));

                            $this->notice->sendPushNotification("01", 'Transaction Successful', 'Hello, ' . $auth->username . ' Your purchase of ' . $transaction['type'] . ' from KoboSquare has been submitted successfully!', array($auth->token), null, null);
                            return $this->sendResponse($transaction, "Your purchase of " . $transaction['type'] . " from KoboSquare has been submitted successfully!");
                        }
                        else{
                            return $this->sendError('error', 'oOps, Something wend wrong!!!');
                        }
                    }
                    else{
                        return $this->sendError('error', 'oOps, You have Insufficient Fund in your wallet!!!');
                    }
                }
                else{
                    return $this->sendError('Transaction failed', 'Your pin is incorrect');
                }
            }
            else{
                return $this->sendError('error', 'oOps, Please verify your bvn to continue enjoying our services!!!');
            }
        }
        else{
            return $this->sendError('error', 'Invalid Amount!');
        }
    }

    public function validateBill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required',
            'bill_entity_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        }

        $response = $this->ringo->validateBillPayment($request->bill_entity_id, $request->number);

        if($response != null){
            if ($response['status'] == '200')
                $response = $this->sendResponse($response['customerName'], 'Validation Successful');
            else
                $response = $this->sendError('error', $response['message']);
        } else
            $response = $this->sendError('error', 'oOps! Could not validate!!');

        return $response;
    }


    public function getBillByType(Request $request, $type)
    {
        $bills = BillEntity::where('type', $type)->get();
        if(isset($bills))
            return $this->sendResponse($bills, 'Bills');
        else
            return $this->sendError('Error', 'oOps, Something went wrong!!!');
    }

    public function getSubBill(Request $request, $sub_id)
    {
        $bills = BillSubEntity::where('bill_entity_id', $sub_id)->get();
        if(isset($bills))
            return $this->sendResponse($bills, 'Sub Bill Entities');
        else
            return $this->sendError('Error', 'oOps, Something went wrong!!!');
    }

}
