<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\KoboService;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DriverAuthController extends BaseController
{
    private $kobo;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
    }

    public function driver_login(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $phone = $request->phone;

        $dr = Driver::where('phone', $request->phone)->first();
        if (!isset($dr)) {
            return $this->sendError('error', 'Phone number does not exist. Please go back to join our driver to get started');
        }
        else if(isset($dr) && $dr->phoneVerified == 0){
            return $this->sendError('error', 'Phone number not verified. Please login on web to verify you phone number');
        }else if(isset($dr->phone) &&  $dr->phoneVerified == 1 && isset($dr->email) && $dr->emailVerified == 1 ) {
            $sendOtp2 = $this->kobo->generateMailAndAPIDriverToken($request->phone,$dr->email,$dr->name);
            if($sendOtp2){
                return $this->sendResponse('Success', 'Check your phone and email an otp has been sent');
            }
            else{
                return $this->sendError('Error', 'Oops! Something went wrong from sending email');
            }

        }else if(isset($dr) && $dr->phoneVerified == 1  &&  $dr->emailVerified == 0){
             $sendOtp = $this->kobo->generateAPIDrToken($request->phone);
            // $sendOtp = $this->kobo->generateAPIToken($request->phone);
                //$this->kobo->phone_send_verification($request->phone);
            if($sendOtp){
                return $this->sendResponse('Success', 'Check your phone otp has been sent');
            }else{
                return $this->sendError('Error', 'Oops! unable to send otp, something went wrong');
            }
         }
        else{
            return $this->sendError('Error', 'Oops! Something went wrong');
        }
    }

    public function verify_otp_driver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        }
        $otp = $request->otp;
        $dr = Driver::where('phone', $request->phone)->first();
        //dd($otp, $request->phone);
        //$verify = $this->kobo->verify_phone($otp, $request->phone);
        $verify = $this->kobo->verifyAPIDOtp($request->phone, $otp);
        //if ($verify->valid) {
        if ($verify) {

            if(isset($dr->email) &&  $dr->emailVerified == 1 ){
                $msg="You are receiving this email because you log in to your account on";
                $subject="koboSquare Login Notification";
                $this->kobo->anyEmailNotification($dr->email,$dr->name,$msg,$subject);
            }

            Auth::guard('driver')->login($dr);
            $success['token'] = $dr->createToken('Laravel Password Access Client')->accessToken;
            $success['driver'] = Auth::guard('driver')->user();
            return $this->sendResponse($success, 'Login successfully');
        } else {
            return $this->sendError('Error', 'Oops! Invalid Otp code');
        }
    }

    public function getuser()
    {
        $success = Auth::guard('driver_api')->user();
        return $this->sendResponse($success, 'Login successfully');
    }
}
