<?php

namespace App\Http\Controllers\Api;

use App\Services\KoboService;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

use App\Mail\LoginNotificationEmail;

class AuthController extends BaseController
{
    private $kobo;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
    }

    public function register(Request $request)
    {

        if ($request->phone && !$request->otp && !$request->email && !$request->username)
            return $this->phone_registration($request);
        else if ($request->phone && $request->otp)
            return $this->phone_verify($request);
        // else if ($request->phone && $request->email && !$request->password) {
        //     return $this->email_registration($request);
        // }
        else if ($request->phone && $request->email)
            return $this->user_profile($request);
        else if ($request->phone && $request->username)
            return $this->add_username($request);
        else
            // return 'null';
            return "input cannot be empty";
    }

    public function phone_registration($req)
    {
        $req->validate([
            'phone' => 'required'
        ]);
        $phoneVerified = User::where('phone', $req->phone)->where('phoneVerified', 1)->first();
        // $token = $phoneVerified->createToken('Laravel Password Access Client')->accessToken;
        // dd($token);
        $phoneNotVerified = User::where('phone', $req->phone)->where('phoneVerified', 0)->first();
        if (isset($phoneVerified)) {
            return $this->sendError('Error', 'Oops! Phone Number already exist and verified');
        } else if (isset($phoneNotVerified)) {
            if ($req->resend == true) {
                //$this->kobo->phone_send_verification($req->phone);
                $sendOtp = $this->kobo->generateAPIToken($req->phone);
                if($sendOtp){
                    return $this->sendResponse('Success', 'Check your phone otp has been resent');
                }
                else{
                    return $this->sendError('Error', 'Oops! Something went wrong');
                }
            } else {
                return $this->sendError('Error', 'Oops! Phone Number exist but not verified');
            }

        } else {
            $phoneInsert = new User();
            $phoneInsert->phone = $req->phone;
            $phoneInsert->save();
            //$this->kobo->phone_send_verification($req->phone);
            $sendOtp = $this->kobo->generateAPIToken($req->phone);
            if($sendOtp){

                return $this->sendResponse('Success', 'Check your phone otp has been sent');
            }
            else{
                return $this->sendError('Error', 'Oops! Something went wrong');
            }
        }
    }

    public function phone_verify($req)
    {
        $req->validate([
            'phone' => 'required',
            'otp' => 'required|min:6|max:6'
        ]);
        $user = User::where('phone', $req->phone)->where('phoneVerified', 0)->first();
        if(!$user){
            return $this->sendError('Error', 'user not registered');
        }
        //$verify = $this->kobo->verify_phone($req->otp, $req->phone);
        $verify = $this->kobo->verifyAPIOtp($req->phone, $req->otp);
        if ($verify) {
            $user->phoneVerified = 1;
            $user->otp = '';
            $user->update();
            return $this->sendResponse('Success', 'Phone number verified successfully');
        } else {
            return $this->sendError('Error', 'Oops! Invalid Otp code');
        }
    }

    public function email_registration(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'email' => 'required|email'
        ]);
        //$userEmail = User::where('phone', $request->phone)->where('phoneVerified', 1)->first();
        $phoneVerified = User::where('phone', $request->phone)->first();
        $emailVerified = User::where('phone', $request->phone)->where('email', $request->email)->where('emailVerified', 1)->first();
        $emailNotVerified = User::where('phone', $request->phone)->where('email', $request->email)->where('emailVerified', 0)->first();
        if (isset($emailVerified)) {
            return $this->sendError('Error', 'Oops! Email already exist and verified');
        }
        if (isset($phoneVerified)) {
            return $this->sendError('Error', 'Oops! Phone Number already exist and verified');
        }
        if (isset($emailNotVerified)) {
            if ($request->resend == true) {
                $name="";


                $token = $this->kobo->generateEmailToken($request->phone, $request->email , $name);

                // $fullname = "";
                // $msg="You just created a profile on Kobosquare";
                // $subject="KoboSquare Email Veri";
                // $token=$this->kobo->anyEmailNotification($request->email, $fullname, $msg,$subject);

                if($token){
                    return $this->sendResponse('Success', 'Check your email, otp has been resent');
                }
                else{
                    return $this->sendError('Error', 'Oops! Something went wrong!!!');
                }
            } else {
                return $this->sendError('Error', 'Oops! Email exist but not verified');
            }

        } else {
            
            $phoneInsert = new User();
            $phoneInsert->phone = $request->phone;
            $phoneInsert->email = $request->email;
            $phoneInsert->save();
            $name ="";
            $token = $this->kobo->generateEmailToken($request->phone, $request->email, $name);
            if($token){

                return $this->sendResponse('Success', 'Check your email, otp has been sent');
            }
            else{
                return $this->sendError('Error', 'Oops! Something went wrong!!!');
            }
        }
    }
    public function email_verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|min:6|max:6'
        ]);
        $user = User::where('email', $request->email)->where('emailVerified', 0)->first();
        if ($user->otp != $request->otp) {
            return $this->sendError('Error', 'Oops! Invalid Otp code');
        } else {
            $user->otp = '';
            $user->emailVerified = 1;
            $user->phoneVerified = 1;
            $user->update();
            return $this->sendResponse($user, 'Email verified successfully');
        }
    }

    public function user_profile($req)
    {
        $validator = Validator::make($req->all(), [
            'phone' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        //$emailVerified = User::where('phone', $req->phone)->where('email', $req->email)->where('emailVerified', 1)->first();
        //$emailNotVerified = User::where('phone', $req->phone)->where('email', $req->email)->where('emailVerified', 0)->first();
        // if (isset($emailNotVerified)) {
        //     return $this->sendError('Error', 'Oops! Email already exist but not verified, please verify your email, or choose another one');
        // }
        // else if(isset($emailVerified)){
        //     return $this->sendError('Error', 'Oops! Email already exist and verified, please enter a new one.');
        // }
        // else {
            $user = User::where('phone', $req->phone)->where('phoneVerified', 1)->first();
            if(!$user){
                return $this->sendError('Error', 'wrong phone no, user does not exist');
            }
            if(isset($req->email)){

                //$user->email = $req->email;
                $user->name = $req->first_name . ' ' . $req->last_name;
                $user->update();


                $fullname = $req->first_name . ' ' . $req->last_name;
                $msg="You just created a profile on Kobosquare";
                $subject="KoboSquare Profile Creation";
                $this->kobo->anyEmailNotification($req->email, $fullname, $msg,$subject);

            }
            else{
                $user->name = $req->first_name . ' ' . $req->last_name;
                $user->update();
            }

            return $this->sendResponse('Success', 'User details added successfully please continue');
        

    }


    public function add_username($req)
    {
        $validator = Validator::make($req->all(), [
            'phone' => 'required',
            'username' => 'required|unique:users',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $username_exist = User::where('phone', $req->phone)->where('username', $req->username)->where('phoneVerified', 1)->first();
        if (isset($username_exist)) {
            return $this->sendError('Error', 'Oops! This ID already exist, please enter a new one.');
        } else {
            $user = User::where('phone', $req->phone)->where('phoneVerified', 1)->first();
            if(!$user){
                return $this->sendError('Error', 'user not found');
            }
            $user->username = $req->username;
            $user->update();
            $wallet = new UserWallet();
            $wallet->user_id = $user->id;
            $wallet->save();
            //$success['token'] = $user->createToken('apiToken')->accessToken;
            return $this->sendResponse('success', 'Account created successfully');
        }
    }


    public function addPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'pin' => 'required|max:4|confirmed|min:4',
            'pin_confirmation' => 'required|max:4|min:4',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        } else {
            $user = User::where('phone', $request->phone)->where('phoneVerified', 1)->first();
            $pin = bcrypt($request->pin);
            $user->pin = $pin;
            $sv = $user->update();
            if($sv){
                Auth::login($user);
                $success['token'] = $user->createToken('Laravel Password Access Client')->accessToken;
                $success['user'] = Auth::user();
               return $this->sendResponse($success, 'Pin is successfully created and login successfully');
            }
            else{
                return $this->sendError('error', 'oOps! something went wrong');
            }
        }
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'string|email|max:255',
            'phone' => 'string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
        //$loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        
        $user = User::where('phone', $request->phone)->orWhere('email', $request->email)->first();
        if(!$user){
            return $this->sendError('Error', 'user not registered');
        }
        if ($user->name != '' && $user->username != '') {
            
            $sendOtp = $this->kobo->generateAPIToken($user->phone);
            if($sendOtp){
                return $this->sendResponse('Success', 'Check your phone otp has been sent');
            }
            else{
                return $this->sendError('Error', 'Oops! Something went wrong');
            }
        } else {
            return $this->sendError('Error', 'Oops! Please completed your registration to enjoy our services');
        }

        // if ($user) {
        //     if (Hash::check($request->password, $user->password)) {
        //         $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        //         $response = ['token' => $token];
        //         return response($response, 200);
        //     } else {
        //         $response = ["message" => "Password mismatch"];
        //         return response($response, 422);
        //     }
        // } else {
        //     $response = ["message" => 'User does not exist'];
        //     return response($response, 422);
        // }
    }


    public function loginn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'string|email|max:255',
            'phone' => 'string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
        $user = User::where('phone', $request->phone)->first();
        // $user = User::where('phone', $request->phone)->where('email',$request->email)->first();
        if(!$user){
            return $this->sendError('Error', 'User not registered');
        }
        if (isset($user->phone)) {

            if(isset($user->phone) && empty($user->email)) {
                $sendOtp = $this->kobo->generateAPIToken($user->phone);
                if($sendOtp){
                    return $this->sendResponse('Success', 'Check your phone an otp has been sent');
                }
                else{
                    return $this->sendError('Error', 'Oops! Something went wrong');
                }

            } else if( isset($user->phone) && isset($user->email)  ) {
                $sendOtp2 = $this->kobo->generateAPIAndMailToken($user->phone,$user->email,$user->name);
                if($sendOtp2){
                    return $this->sendResponse('Success', 'Check your phone and email an otp has been sent');
                }
                else{
                    return $this->sendError('Error', 'Oops! Something went wrong');
                }
            } else if(isset($user->phone) &&  $user->phoneVerified == 1  &&  empty($user->email) ) {
                $sendOtp = $this->kobo->generateAPIToken($user->phone);
                if($sendOtp){
                    return $this->sendResponse('Success', 'Check your phone an otp has been sent');
                }
                else{
                    return $this->sendError('Error', 'Oops! Something went wrong');
                }

            }
        } else {
            return $this->sendError('Error', 'this Phone Number does not exist , register to contine using our service');
        }
    }


    // public function verify_login(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'otp' => 'required|numeric',
    //         'phone' => 'required|min:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError('Validation Error.', $validator->errors());
    //     }

    //     $user = User::where('phone', $request->phone)->where('phoneVerified', 1)->first();

    //     if ($user) {
    //         $verify = $this->kobo->verifyAPIOtp($request->phone, $request->otp);

    //         if ($verify) {
    //             // Check if the user has a verified email
    //             if (isset($user->email) && $user->emailVerified == 1) {
    //                 $msg = "You are receiving this email because you logged in to your account on";
    //                 $subject = "koboSquare Login Notification";
    //                 $this->kobo->anyEmailNotification($user->email, $msg, $subject);
    //             }

    //             // Authenticate the user
    //             Auth::login($user);

    //             // Ensure the user is authenticated before creating a token
    //             if (Auth::check()) {
    //                 $success['token'] = $user->createToken('Laravel Password Access Client')->accessToken;
    //                 $success['user'] = Auth::user();
    //                 Session::forget('code');
    //                 return $this->sendResponse($success, 'Login successfully');
    //             } else {
    //                 return $this->sendError('Error', 'User authentication failed');
    //             }
    //         } else {
    //             return $this->sendError('Error', 'Oops! Invalid OTP code');
    //         }
    //     } else {
    //         return $this->sendError('Error', 'User not found or phone not verified');
    //     }
    // }





    public function verify_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $user = User::where('phone', $request->phone)->where('phoneVerified', 1)->first();
        //$verify = $this->kobo->verify_phone($request->otp, $request->phone);
        $verify = $this->kobo->verifyAPIOtp($request->phone, $request->otp);
        if ($verify) {
            if($user->phoneVerified == 0){
                $user->phoneVerified = 1;
                $user->otp = '';
                $user->update();
            }
            // if (Auth::check()) {
                if(isset($user->email)){
                       $msg="You are receiving this email because you log in to your account on";
                       $subject="koboSquare Login Notification";
                       $this->kobo->anyEmailNotification($user->email,$user->name,$msg,$subject);
                   }
            Auth::login($user);
            $success['token'] = $user->createToken('Laravel Password Access Client')->accessToken;
            $success['user'] = Auth::user();
            Session::forget('code');
            return $this->sendResponse($success, 'Login successfully');
        // } else {
        //     return $this->sendError('error', 'user is not authenticated!');
        // }


        } else {
            return $this->sendError('Error', 'Oops! Invalid Otp,Ensure the OTP is correct');
        }
    }

    public function resend_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        //$this->kobo->phone_send_verification($request->phone);
        $sendOtp = $this->kobo->generateAPIToken($request->phone);
        //$this->kobo->phone_send_verification($request->phone);
        if($sendOtp){
            return $this->sendResponse('Success', 'Check your phone otp has been resent');
        }
        else{
            return $this->sendError('error', 'oOps, Something went wrong!!!');
        }

    }



    public function getBanks()
    {
        $banks = DB::table('bank_entities')
            ->select('bank_code', 'bank_name')->where('status', 'active')->get();

        return $this->sendResponse($banks, 'All Active Bank');
    }
}
