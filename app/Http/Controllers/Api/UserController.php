<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\Driver;
use App\Models\TableEntity;
use App\Models\User;
use App\Models\UserBank;
use App\Models\UserAddress;
use App\Models\UserWallet;
use App\Notifications\KoboNotification;
use App\Services\KoboService;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification as NotificationsNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Services\PushNotification;

class UserController extends BaseController
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
    public function index()
    {
        $user = Auth::guard('api')->user();
        return $this->sendResponse($user, 'User Data');
    }


    public function CheckUsername(Request $request)
    {
        $duplicate_user = DB::table('users')
            ->where('username', 'LIKE', $request->username)
            ->first();

        if ($duplicate_user) {
            return $this->sendError('warning', "This username had already been taken!!!");
        } else {
            return $this->sendResponse('success', "Username available");
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function verifyBvn(Request $request)
    {
        $client = Auth::guard('api')->user();
        $checkbvn = User::where('bvn', $request->bvn)->first();
        if (isset($checkbvn)) {
            return $this->sendError('error', 'Oops!, bvn already exit and verified');
        } else {
            if (!isset($client->bvn)) {

                if ($this->kobo->bvnMatch($request)) {
                    unset($request->bank_code);
                    return $this->sendResponse($request, 'BVN Verified Successfully');
                } else {
                    return $this->sendError($request, 'BVN Verification Failed');
                }
            } else
                return $this->sendError($request, 'BVN Already Verified');
        }
    }


    public function createPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            if ($sv) {
                return $this->sendResponse('success', 'Pin is successfully created');
            } else {
                return $this->sendError('error', 'oOps! something went wrong');
            }
        }
    }
    
    public function check_pin(Request $request)
    {
        $auth = Auth::guard('api')->user();
        if ($request->pin && Hash::check($request->pin, $auth->pin)) {
            return $this->sendResponse('success', 'Pin is correct');
        }
        else{
                return $this->sendError('error', 'Pin is invalid');
            }
    }

    /**
     * Display the specified resource.
     */
    public function addBank(Request $request)
    {
        $client = Auth::guard('api')->user();
        if (isset($client->bvn)) {
            $bs = $this->kobo->bvnBankVerification($request);
            if ($bs == true) {
                return $this->sendResponse($request, 'Bank added successfully');
            } elseif ($bs == 'exist') {
                return $this->sendError($request, 'Bank account already added, please choose another account');
            } else {
                return $this->sendError($request, 'Bank account doesn\'t match with you bvn');
            }
        } else {
            return $this->sendError($request, 'oOps! please verify you bvn to continue');
        }
    }

    /**
     * Change Password the specified resource in storage.
     */
    // public function changePassword(Request $request)
    // {
    //     $data = $request->all();
    //     $user = Auth::guard('api')->user();

    //     //Changing the password only if is different of null
    //     if (isset($data['oldPassword']) && !empty($data['oldPassword']) && $data['oldPassword'] !== "" && $data['oldPassword'] !== 'undefined') {
    //         //checking the old password first
    //         $check = Auth::guard('web')->attempt([
    //             'username' => $user->username,
    //             'password' => $data['oldPassword']
    //         ]);
    //         if ($check && isset($data['newPassword']) && !empty($data['newPassword']) && $data['newPassword'] !== "" && $data['newPassword'] !== 'undefined') {
    //             $user->password = bcrypt($data['newPassword']);
    //             $user->token()->revoke();
    //             $token = $user->createToken('newToken')->accessToken;

    //             //Changing the type
    //             $user->save();
    //             return $this->sendResponse($token, 'password changed successfully');
    //         } else {
    //             return $this->sendError('Error', 'Wrong password information');
    //         }
    //     }
    //     return $this->sendError('Error', 'Wrong password information');
    // }

    /**
     * Change Pin the specified resource from storage.
     */
    public function changePin(Request $request)
    {
        $data = $request->all();
        $user_data = Auth::guard('api')->user();
        $user = User::where('id', $user_data->id)->first();
        // Changing the pin only if is different of null
        if (isset($data['oldPin']) && !empty($data['oldPin']) && $data['oldPin'] !== "" && $data['oldPin'] !== 'undefined') {
            // checking the old pin first
            if (Hash::check($data['oldPin'], $user->pin) && isset($data['newPin']) && !empty($data['newPin']) && $data['newPin'] !== "" && $data['newPin'] !== 'undefined') {
                $user->pin = bcrypt($data['newPin']);
                $user->update();


                if(isset($user_data->email) && $user_data->emailVerified == 1 ){
                    $msg="You have successfully changed your pin";
                    $subject="Kobosquare Pin Notification";
                    $this->kobo->anyEmailNotification($user_data->email,$user_data->name, $msg,$subject);
                }
                //   $this->kobo->changePinNotification($user->email);

                return $this->sendResponse('success', 'Pin changed successfully');
            } else {
                return $this->sendError('Error', 'Wrong pins information');
            }
        }
        return $this->sendError('Error', 'Wrong pin information');
    }


    public function checkPin(Request $request)
    {
        $validatedData = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        $user = Auth::guard('api')->user();

        if (Hash::check($validatedData['pin'], $user->pin)) {
            return $this->sendResponse('success', 'Pin is correct');
        } else {
            return $this->sendError('Error', 'Invalid pin information');
        }
    }



    public function logoutApi()
    {
        $refreshTokenRepository = app(\Laravel\Passport\RefreshTokenRepository::class);
        foreach (User::find(Auth::user()->id)->tokens as $token) {
            $token->revoke();
            $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->id);
        }

        return true;
    }

    public function sendNotice()
    {
        $drivers = Driver::all();
        //$user->notify(new KoboNotification('hello'));
        $message = [
            "title" => "Data Purchase",
            "message" => 'You just purchase a data'
        ];

        //dd($message[title]);

        Notification::send($drivers, new KoboNotification($message));
    }

    public function getUserNotification()
    {
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        return $this->sendResponse($user->notifications, 'User Notifications');
    }

    public function readUserNotification($notice_id)
    {
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $user->unreadNotifications->where('id', $notice_id)->markAsRead();
        return $this->sendResponse('success', 'Notification read successfully');
    }

    public function readAllUserNotification()
    {
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $user->unreadNotifications->markAsRead();
        return $this->sendResponse('success', 'All marked Notifications read successfully!!!');
    }


    public function deleteAllUserNotification()
    {
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $user->notifications()->delete();
        return $this->sendResponse('success', 'All notifications deleted successfully');
    }

    public function uploadUserAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        } else {
            if ($request->has('photo')) {
                $blfilename = Str::random(7) . '.' . $request->photo->extension();

                $request->photo->move(public_path('user_photo'), $blfilename);


                $user = User::where('id', Auth::guard('api')->user()->id)->first();
                $user->avatar = $blfilename;
                $sv = $user->update();
                if ($sv) {
                    return $this->sendResponse('success', 'User Avatar Uploaded Successfully!!!');
                } else {
                    return $this->sendError('Error', 'oOps!, Something went wrong!!');
                }
            }
        }
    }

    public function getUserAvatar()
    {
        $user = User::where('id', Auth::guard('api')->user()->id)->first();

        $data = [
            'path' => 'https://api.kobosquare.com/user_photo/',
            'avatar' => $user->avatar
        ];

        return $this->sendResponse($data, 'User Avatar');
    }


    public function getUserAddress()
    {
        $data = UserAddress::where('user_id', Auth::guard('api')->user()->id)->get();
        return $this->sendResponse($data, 'User Addresses');
    }


    public function addUserAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        } else {
            $address = $request->address;
            $result = app('geocoder')->geocode($address)->get();

            if (isset($result[0])) {
                $coordinates = $result[0]->getCoordinates();
                $lat = $coordinates->getLatitude();
                $long = $coordinates->getLongitude();
            } else {
                return $this->sendError('Error', 'Oops! Unable to geocode the address.');
            }
            // $coordinates = $result[0]->getCoordinates();
            // $lat = $coordinates->getLatitude();
            // $long = $coordinates->getLongitude();
            $add = UserAddress::where('user_id', Auth::guard('api')->user()->id)->where('label', 'home')->first();
            $add2 = UserAddress::where('user_id', Auth::guard('api')->user()->id)->where('label', 'work')->first();
            if (isset($add) and $request->label == 'home') {
                return $this->sendError('Error', 'oOps!, Home address already Exist!!');
            } else if (isset($add2) and $request->label == 'work') {
                return $this->sendError('Error', 'oOps!, Work address already Exist!!');
            } else {
                $ua = new UserAddress();
                $ua->user_id = Auth::guard('api')->user()->id;
                $ua->label = $request->label;
                $ua->address = $address;
                $ua->longitude = $long;
                $ua->latitude = $lat;
                $sv = $ua->save();
                if ($sv) {
                    return $this->sendResponse($sv, 'Address Successfully added!!');
                } else {
                    return $this->sendError('Error', 'oOps!, Something went wrong!!');
                }
            }
        }
    }


    //update user address
    public function updateUserAddress(Request $request, $addressId)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        } else {
            $user = Auth::guard('api')->user();

            $address = $request->address;

            // Make a request to Google Geocoding API
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => 'AIzaSyDp32iWaShk9E_wTNtJbAkNXqdishmZnE8',
            ]);

            $data = $response->json();

            if ($response->successful() && $data['status'] === 'OK') {
                $coordinates = $data['results'][0]['geometry']['location'];

                $userAddress = UserAddress::where('user_id', $user->id)->where('id', $addressId)->first();

                if (!$userAddress) {
                    return $this->sendError('Error', 'Oops!, user address not found!');
                }

                $userAddress->update([
                    'label' => $request->label,
                    'address' => $request->address,
                    'latitude' => $coordinates['lat'],
                    'longitude' => $coordinates['lng'],
                ]);

                return $this->sendResponse($userAddress, 'User Address updated successfully');
            } else {
                return $this->sendError('Error', 'Unable to geocode the updated address.');
            }
        }
    }


    // public function updateUserAddress(Request $request, $addressId)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'label' => 'required',
    //         'address' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError('Validation Error.', $validator->errors());
    //     } else {
    //         $user = Auth::guard('api')->user();

    //         $userAddress = UserAddress::where('user_id', $user->id)
    //                                   ->where('id', $addressId)
    //                                   ->first();

    //         if (!$userAddress) {
    //             return $this->sendError('Error', 'Oops!, user address not found!');
    //         }

    //         $address = $request->address;
    //         $result = app('geocoder')->geocode($address)->get();

    //         if (empty($result)) {
    //             return $this->sendError('Error', 'Unable to geocode the updated address.');
    //         }

    //         $coordinates = $result[0]->getCoordinates();

    //         $userAddress->update([
    //             'label' => $request->label,
    //             'address' => $request->address,
    //             'latitude' => $coordinates->getLatitude(),
    //             'longitude' => $coordinates->getLongitude(),
    //         ]);

    //         return $this->sendResponse($userAddress, 'User Address updated successfully');
    //     }
    // }

    public function deleteUserAddress($addressId)
    {
        $user = Auth::guard('api')->user();

        $userAddress = UserAddress::where('user_id', $user->id)->where('id', $addressId)->first();

        if (!$userAddress) {
            return $this->sendError('Error', 'Oops!, user address not found!');
        }

        $userAddress->delete();

        return $this->sendResponse([], 'User Address deleted successfully');
    }


    public function profileProgress()
    {
        $user = Auth::guard('api')->user();
        $per = 100;
        if ($user->bvn == '') {
            $bvnPercent = 20;
        } else {
            $bvnPercent = 0;
        }

        if ($user->username == '') {
            $username = 10;
        } else {
            $username = 0;
        }

        if ($user->pin == '') {
            $pin = 10;
        } else {
            $pin = 0;
        }

        if ($user->phoneVerified == '') {
            $phone = 10;
        } else {
            $phone = 0;
        }

        $user_bank = UserBank::where('user_id', $user->id)->first();
        if (isset($user_bank)) {
            $ubank = 0;
        } else {
            $ubank = 20;
        }

        $total_per = 100 - $bvnPercent - $username - $ubank - $pin - $phone;
        $data = [
            "profile_percent" =>  $total_per
        ];


        return $this->sendResponse($data, 'User Profile Percentage');
    }


    public function withdrawToBank(Request $request)
    {
        $inputs = $request->input();

        $client = Auth::guard('api')->user();

        if ($request->pin && Hash::check($request->pin, $client->pin)) {

            if ($client->bvn != null) {

                $isVerified = $client->status;

                if ($isVerified == 'active') {

                    if ($inputs['amount'] > 0) {

                        $pendingPayout = $this->getPendingPayout($client->id);

                        if ($pendingPayout == null) {

                            $accountBal = $this->getWalletBalance($client->id);

                            if ($accountBal->balance > $inputs['amount']) {

                                $payoutRequest = $this->savePayoutRequest($request, $client);

                                //           \Log::info($payoutRequest);

                                if ($payoutRequest != null) {

                                    $this->kobo->handlesWithdrawalAutomation($payoutRequest, $client->id, $inputs);

                                    if (isset($client->email) && $client->emailVerified == 1) {
                                        $msg = "Withdrawal request of ₦" . $inputs['amount'] . " has been submitted successfully!";
                                        $subject = "Kobosquare Withdrawal Notification";
                                        $this->kobo->anyEmailNotification($client->email, $client->name, $msg, $subject);
                                    }

                                    $authmsg = "Successful Withdrawal, Withdrawal request of ₦"  . $inputs['amount'] . "  has been submitted successfully! ";
                                    Notification::send($client, new KoboNotification(['title' => 'Transaction Successful', 'message' => $authmsg]));

                                    $this->notice->sendPushNotification("01", 'Withdrawal Successful', 'Hello, ' . $client->username . ' YourWithdrawal request of ₦' . $inputs['amount'] . ' has been submitted successfully!', array($client->token), null, null);

                                    return $this->sendResponse($payoutRequest, 'Withdrawal request of ₦' . $inputs['amount'] . ' has been submitted successfully!');
                                } else

                                    return $this->sendError('error', "Payout Request Fail...Try Again!");
                            } else
                                return $this->sendError('warning', 'oOps, you have insufficient balance to complete this transaction');
                        } else
                            return $this->sendError('warning', 'oOps, You have a pending payout request');
                    } else

                        return $this->sendError('error', 'oOps, the amount you entered is invalid');
                } else

                    return $this->sendError('error', 'oOps, your account is not verified');
            } else

                return $this->sendError('error', 'oOps, Please verify your bvn');
        } else

            return $this->sendError('error', 'oOps, the pin you entered is incorrect');
    }


    public function getPendingPayout($user_id)
    {
        return DB::table('transactions')->where('user_id', $user_id)->where('status', 'Pending')->first();
    }


    public function savePayoutRequest($inputs, $user)
    {

        $data = [

            'user_id' => $user->id,

            'mac_address' => $inputs->mac_address,

            'ip_address' => $inputs->ip_address,

            'latitude' => $inputs->latitude,

            'longitude' => $inputs->longitude,

            'status' => 'Pending',

            'to' => $inputs->account_number,
            
            'customer_name' => isset($inputs->customer_name) ? $inputs->customer_name : '' ,
            
            'bank_name' => isset($inputs->bank_name) ? $inputs->bank_name : '',

            'amount' => $inputs->amount,

            'type' => 'Withdrawal',

            'from' => 'Wallet',

            'method' => 'withdraw',

            'trans_ref' => 'KBS-' . Str::random(7) . '-' . Str::random(4) . '-' . Str::random(8),

            'platform' => $inputs->platform,

        ];

        return $this->table->insertNewEntry('transactions', 'id', $data);
    }


    public function getWalletBalance($client_id)
    {
        return UserWallet::where('user_id', $client_id)->first();
    }

    public function updatePushTokenUser(Request $request)
    {
        $client = Auth::guard('api')->user();
        $user = User::where('id', $client->id)->first();
        $user->token = $request->token;
        $user->update();
        return $user;
    }

    public function UserDeleteAccount($account_id)
    {
        $client = Auth::guard('api')->user();
        $account = UserBank::where('id', $account_id)->where('user_id', $client->id)->first();
        if (isset($account)) {
            $del = $account->delete();
            if ($del) {
                return $this->sendResponse($del, 'Bank account removed successfully');
            } else {
                return $this->sendError('error', 'oOps! something went wrong');
            }
        } else {
            return $this->sendError('error', 'oOps! bank account does not exist!!!');
        }
    }


    public function savePinLock()
    {
        $client = Auth::guard('api')->user();
        $user = User::where('id', $client->id)->first();

        $pinLockStatus = $user->save_pin_lock;

        if ($pinLockStatus == 1) {
            return $this->sendResponse(['disabled' => true], 'user pin lock status has already been added. pin lock status button disabled.');
        } else {
            $user->save_pin_lock = 1;
            $sv = $user->update();
            if ($sv) {
                return $this->sendResponse('success', 'pin lock status is now added');
            } else {
                return $this->sendError('Error', 'oOps!, Something went wrong!!');
            }
        }

    }

    public function removePinLock()
    {
        $client = Auth::guard('api')->user();
        $user = User::where('id', $client->id)->first();

        $pinLockStatus = $user->save_pin_lock;

        if ($pinLockStatus == 0) {
            return $this->sendResponse(['disabled' => false], 'user pin lock status has already been removed. pin lock status button enabled.');
        } else {
            $user->save_pin_lock = 0;
            $sv = $user->update();
            if ($sv) {
                return $this->sendResponse('success', 'pin lock status now removed');
            } else {
                return $this->sendError('Error', 'oOps!, Something went wrong!!');
            }
        }
    }

    public function checkPinLockStatus()
    {
        $client = Auth::guard('api')->user();
        $user = User::where('id', $client->id)->first();

        $pinLockStatus = $user->save_pin_lock;

        if ($pinLockStatus == 0) {
            return $this->sendResponse(['disabled' => false], 'user pin lock status not saved. pin lock status button enabled.');
        } else {
            return  $this->sendResponse(['disabled' => true], 'user pin lock status is saved to lock. pin lock status button disabled.');
        }
    }
}
