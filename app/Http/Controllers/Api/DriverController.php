<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverLocationUpdated;
use  App\Events\BookingChatMessageCreated;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\BookingChat;
use App\Models\BookingChatMessage;
use App\Models\TableEntity;
use App\Models\Driver;
use App\Models\DriverBank;
use App\Models\DriverVehicle;
use App\Models\DriverWallet;
use App\Models\DriverWithrawalHistory;
use App\Models\TripHistory;
use App\Models\User;
use App\Services\KoboService;
use App\Services\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DriverController extends BaseController
{
    private $kobo, $table, $notice;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
        $this->table = new TableEntity();
        $this->notice = new PushNotification();
    }

    public function index()
{
    $driver = Auth::guard('driver_api')->user();
    $driverVehicles = DriverVehicle::where('driver_id', $driver->id)->get();
    $userData = [
        'user' => $driver,
        'vehicles' => $driverVehicles
    ];
    return $this->sendResponse($userData, 'Driver Data');
}


    // public function index()
    // {
    //     $user = Auth::guard('driver_api')->user();
    //     return $this->sendResponse($user, 'User Data');
    // }

    public function updatePushTokenDriver(Request $request)
    {
        $client = Auth::guard('driver_api')->user();
        $user = Driver::where('id', $client->id)->first();
        $user->token = $request->token;
        $user->update();
        return $user;
    }

    public function updateCoordinate(Request $request)
    {
        $id = Auth::guard('driver_api')->user()->id;
        $driver = Driver::findOrFail($id);
        $driver->c_lat = $request->latitude;
        $driver->c_long = $request->longitude;
        $du = $driver->update();
        if ($du) {
            return $this->sendResponse($driver, 'driver location updated');
        } else {
            return $this->sendError('Error', 'oOps, Something went wrong!!');
        }
    }

    // public function updateDriverLocation(Request $request, $driverId)
    // {
    //     // Validate and process the driver location update...

    //     // Update the driver location...

    //     // Dispatch the DriverLocationUpdated event
    //     event(new DriverLocationUpdated($driverId, $request->latitude, $request->longitude));

    //     return response()->json(['message' => 'Driver location updated successfully']);
    // }

    // public function updateDriverLocation(Request $request, $driverId)
    // {
    //     // Validate the incoming request data
    //     $validator = Validator::make($request->all(), [
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     $driver = Driver::find($driverId);
    //     if (!$driver) {
    //         return response()->json(['error' => 'Driver not found'], 404);
    //     }

    //     $driver->c_lat = $request->latitude;
    //     $driver->c_long = $request->longitude;
    //     $driver->save();

    //     event(new DriverLocationUpdated($driverId, $request->latitude, $request->longitude));

    //     return response()->json(['message' => 'Driver location updated successfully'], 200);
    // }


    public function getDriverLocation($driverId)
    {
        // $driver = Driver::find($driverId);
        $driver= Auth::guard('driver_api')->user();

        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        return response()->json([
            'latitude' => $driver->c_lat,
            'longitude' => $driver->c_long,
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $id = Auth::guard('driver_api')->user()->id;
        $driver = Driver::findOrFail($id);
        if ($driver->availability == $request->availability) {
            return $this->sendError('Error', 'Driver is already ' . $driver->availability);
        } else {
            $driver->availability = $request->availability;
            $du = $driver->update();
            if ($du) {
                return $this->sendResponse($driver, 'driver is now ' . $driver->availability);
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }

    // public function acceptTrip(Request $request)
    // {
    //     $id = Auth::guard('driver_api')->user()->id;
    //     $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->first();


    //     if ($trip->trip_status != 'pending') {
    //         return $this->sendError('Error', 'Trip has been accepted by another driver/not available/does\'nt exist');
    //     } else {
    //         $trip->trip_status = 'accept';
    //         $tu = $trip->update();
    //         $this->initiateTripChat($trip->id);
    //         $user = User::where('id', $trip->user_id)->first();
    //         $driver = Driver::where('id', $trip->driver_id)->first();

    //         $this->notice->sendPushNotification("01", 'Trip Accepted', 'Hi, ' . $user->name . ' your trip order request have been accepted and you driver is on the way, please wait for him.', array($user->token), null, $trip);
    //         $this->notice->sendPushNotification("01", 'Trip Accepted', 'Hi, you just accepted a trip order', array($driver->token), null, $trip);


    //         if ($tu) {
    //             return $this->sendResponse($tu, 'Trip Accepted');
    //         } else {
    //             return $this->sendError('Error', 'oOps, Something went wrong!!');
    //         }
    //     }
    // }

    // public function initiateTripChat($trip_id)
    // {
    //     $init = new BookingChat();
    //     $init->trip_history_id = $trip_id;
    //     $sv = $init->save();
    //     return $sv;
    // }

    // public function replyChat(Request $request)
    // {
    //     $id = Auth::guard('driver_api')->user()->id;
    //     $chat = BookingChat::where('trip_history_id', $request->trip_id)->first();
    //     $new = new BookingChatMessage();
    //     $new->booking_chat_id = $chat->id;
    //     $new->user_entity_id = $id;
    //     $new->text = $request->text;
    //     $sv = $new->save();

    //     if ($sv) {
    //          event(new BookingChatMessageCreated($new));
    //         return $this->sendResponse($new, 'Message Sent');
    //     } else {
    //         return $this->sendError('error', 'Oops, Something wend wring!');
    //     }
    // }


    // public function replyChat(Request $request, $userid)
    // {
    //     $driver = Auth::guard('driver_api')->user();
    //     if (!$driver) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     $chat = BookingChat::where('trip_history_id', $request->trip_id)->where('user_id', $userid)->first();
    //     if (!$chat) {
    //         // Create a new chat if it doesn't exist
    //         $chat = new BookingChat();
    //         $chat->trip_history_id = $request->trip_id;
    //         $chat->user_id = $driver->id;
    //         $chat->driver_id = $userid;
    //         $chat->message = $request->text;
    //         $chat->save();
    //         return $this->sendResponse($chat, 'Chat initiated successfully');
    //     } else {
    //         $new = new BookingChatMessage();
    //         $new->booking_chat_id = $chat->id;
    //         $new->user_entity_id = $driver->id;
    //         $new->text = $request->text;
    //         $sv = $new->save();

    //         if ($sv) {
    //             event(new BookingChatMessageCreated($new));

    //             $user = User::where('id', $userid)->first();
    //             $this->notice->sendPushNotification("01", 'Trip Message', $request->text, array($user->token), null, $new);

    //             return $this->sendResponse($new, 'Message Sent');
    //         } else {
    //             return $this->sendError('error', 'Oops, Something went wrong!');
    //         }
    //     }
    // }



    public function checkTripHistory($userId, $driverId)
    {
        $tripHistoryExists = TripHistory::where('user_id', $userId)->where('driver_id', $driverId)->exists();
        if ($tripHistoryExists) {
            return $this->sendResponse(['disabled' => false], 'Chat enabled. You can start chatting.');
        } else {
            return $this->sendResponse(['disabled' => true], 'Chat disabled. No trip history found.');
        }
    }

    // public function declineTrip(Request $request)
    // {
    //     $id = Auth::guard('driver_api')->user()->id;
    //     $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->where('trip_status', 'pending')->first();
    //     if ($trip->trip_status != 'pending') {
    //         return $this->sendError('Error', 'Trip has been accepted by another driver/not available/does\'nt exist');
    //     } else {
    //         $trip->trip_status = 'decline';
    //         $tu = $trip->update();
    //         if ($tu) {
    //             return $this->sendResponse($trip, 'Trip Declined successfully');
    //         } else {
    //             return $this->sendError('Error', 'oOps, Something went wrong!!');
    //         }
    //     }
    // }

    public function updatePaymentStatus(Request $request)
    {
        $id = Auth::guard('driver_api')->user()->id;
        $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->first();
        if ($trip->payment_status != 'pending' || $trip->trip_status == 'cancelled' || $trip->payment_type != 'cash' || $trip->payment_status != 'unpaid') {
            return $this->sendError('Error', 'Trip payment cannot be updated');
        } else {
            $trip->payment_status = 'paid';
            $dw = DriverWallet::where('driver_id', $id)->first();
            $kobo_percent = 20;
            $kobo_share = ($kobo_percent / 100) * $trip->t_fare;
            $dw->balance = $dw->balance - $kobo_share;
            $dw->update();
            $tu = $trip->update();
            if ($tu) {
                return $this->sendResponse($trip, 'Trip Declined successfully');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }

    // public function cancelTrip(Request $request)
    // {
    //     $id = Auth::guard('driver_api')->user()->id;
    //     $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->first();
    //     if ($trip->trip_status == 'pending' || $trip->trip_status == 'completed' || $trip->trip_status == 'cancelled' || $trip->trip_status == 'decline') {
    //         return $this->sendError('Error', 'Trip cannot be cancelled');
    //     } else {
    //         $trip->trip_status = 'cancelled';
    //         $tu = $trip->update();
    //         if ($tu) {
    //             return $this->sendResponse($trip, 'Trip Cancelled');
    //         } else {
    //             return $this->sendError('Error', 'oOps, Something went wrong!!');
    //         }
    //     }
    // }

    public function updateTripStatus(Request $request)
    {
        $id = Auth::guard('driver_api')->user()->id;
        $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->first();
        if ($trip->trip_status == 'pending' || $trip->trip_status == 'decline' || $trip->trip_status == 'cancelled' || $trip->trip_status == 'completed') {
            return $this->sendError('Error', 'Trip status cannot be updated');
        } else {
            $trip->trip_status = $request->trip_status;
            $tu = $trip->update();
            if ($tu) {
                return $this->sendResponse($trip, 'Trip status updated');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }

    public function pendingTrip()
    {
        $id = Auth::guard('driver_api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->where('trip_status', 'pending')->get();
        $trips = DB::table('trip_histories')->select('trip_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'users.phone as user_phone', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'trip_histories.user_id', '=', 'users.id')
            ->join('drivers', 'trip_histories.driver_id', '=', 'drivers.id')
            ->where('driver_id', $id)
            ->where('trip_status', 'pending')
            ->get();
        return $this->sendResponse($trips, 'Driver\'s pending trips');
    }

    public function completedTrip()
    {
        $id = Auth::guard('driver_api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->where('trip_status', 'completed')->get();
        $trips = DB::table('trip_histories')->select('trip_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'users.phone as user_phone', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'trip_histories.user_id', '=', 'users.id')
            ->join('drivers', 'trip_histories.driver_id', '=', 'drivers.id')
            ->where('driver_id', $id)
            ->where('trip_status', 'completed')
            ->get();
        return $this->sendResponse($trips, 'Driver\'s pending trips');
    }

    public function cancelledTrip()
    {
        $id = Auth::guard('driver_api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->where('trip_status', 'cancelled')->get();
        $trips = DB::table('trip_histories')->select('trip_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'users.phone as user_phone', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'trip_histories.user_id', '=', 'users.id')
            ->join('drivers', 'trip_histories.driver_id', '=', 'drivers.id')
            ->where('driver_id', $id)
            ->where('trip_status', 'cancelled')
            ->get();
        return $this->sendResponse($trips, 'Driver\'s trips');
    }

    public function driverTrip()
    {
        $id = Auth::guard('driver_api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->get();
        $trips = DB::table('trip_histories')->select('trip_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'users.phone as user_phone', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'trip_histories.user_id', '=', 'users.id')
            ->join('drivers', 'trip_histories.driver_id', '=', 'drivers.id')
            ->where('driver_id', $id)
            ->get();
        return $this->sendResponse($trips, 'Driver\'s trips');
    }

    public function driverRating()
    {
        $id = Auth::guard('driver_api')->user()->id;
        $driver = Driver::where('id', $id)->first();
        $trip = TripHistory::where('driver_id', $id)->get();

        $cancel = TripHistory::where('trip_status', 'cancelled')->where('driver_id', $id)->get();

        $completed = TripHistory::where('trip_status', 'completed')->where('driver_id', $id)->get();

        if ($cancel->count() > 0) {
            $cancel_total = ($cancel->count() / $trip->count()) * 100;
        } else {
            $cancel_total = 0;
        }

        if ($completed->count() > 0) {
            $complete_total = ($completed->count() / $trip->count()) * 100;
        } else {
            $complete_total = 0;
        }

        $rating = $driver->ratings()->avg('review_count');

        $data = [
            'completed_trip' => $complete_total,
            'cancel_trip' => $cancel_total,
            'trip_rating' => $rating
        ];

        return $this->sendResponse($data, 'Drivers Rating');
    }

    public function verifyBvn(Request $request)
    {
        $client = Auth::guard('driver_api')->user();
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


    public function createPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|max:4|confirmed|min:4',
            'pin_confirmation' => 'required|max:4|min:4',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        } else {
            $client = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();
            $client_id = $client->id;
            $pin = bcrypt($request->pin);
            $client->pin = $pin;
            $sv = $client->update();
            if ($sv) {
                return $this->sendResponse($sv, 'Pin is successfully created');
            } else {
                return $this->sendError($request, 'oOps! something went wrong');
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function addBank(Request $request)
    {
        $client = Auth::guard('driver_api')->user();
        $validator = Validator::make($request->all(), [
            'account_name' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $bankData = [
            'driver_id' => $client->id,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name
        ];

        $check = DriverBank::where('account_number', $request->account_number)->first();
        if (isset($check)) {
            return $this->sendResponse($request, 'Bank account already exist, try another account');
        } else {
            // Insert Table
            $save = $this->table->insertNewEntry('driver_banks', 'id', $bankData);

            if ($save) {
                return $this->sendResponse($request, 'Bank added successfully');
            } else {
                return $this->sendError('error', 'oOps! something went wrong');
            }
        }
    }

    public function DriverDeleteAccount($account_id)
    {
        $client = Auth::guard('driver_api')->user();
        $account = DriverBank::where('id', $account_id)->where('driver_id', $client->id)->first();
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

    /**
     * Change Password the specified resource in storage.
     */
    // public function changePassword(Request $request)
    // {
    //     $data = $request->all();
    //     $driver = Auth::guard('driver_api')->user();

    //     //Changing the password only if is different of null
    //     if (isset($data['oldPassword']) && !empty($data['oldPassword']) && $data['oldPassword'] !== "" && $data['oldPassword'] !== 'undefined') {
    //         //checking the old password first
    //         if (isset($data['newPassword']) && !empty($data['newPassword']) && $data['newPassword'] !== "" && $data['newPassword'] !== 'undefined') {
    //             $driver->password = bcrypt($data['newPassword']);
    //             $refreshTokenRepository = app(\Laravel\Passport\RefreshTokenRepository::class);
    //             foreach (Driver::find(Auth::guard('driver_api')->user()->id)->tokens as $token) {
    //                 $token->revoke();
    //                 $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->id);
    //             }
    //             //$token = $driver->createToken('newToken')->accessToken;

    //             //Changing the type
    //             $driver->save();
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
        $driver = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();

        // Changing the pin only if is different of null
        if (isset($data['oldPin']) && !empty($data['oldPin']) && $data['oldPin'] !== "" && $data['oldPin'] !== 'undefined') {
            // checking the old pin first
            if (Hash::check($data['oldPin'], $driver->pin) && isset($data['newPin']) && !empty($data['newPin']) && $data['newPin'] !== "" && $data['newPin'] !== 'undefined') {
                $driver->pin = bcrypt($data['newPin']);
                $driver->update();

                if (isset($driver->email) && $driver->emailVerified == 1) {
                    $msg = "your pin has been changed";
                    $subject = "KoboSquare Pin Notification";
                    $this->kobo->anyEmailNotification($driver->email, $driver->name, $msg, $subject);
                }
                return $this->sendResponse('success', 'Pin changed successfully');
            } else {
                return $this->sendError('Error', 'Wrong pins information');
            }
        }
        return $this->sendError('Error', 'Wrong pin information');
    }

    public function fetchAcctName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required',
            'bank_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        } else {
            $fetch = $this->kobo->fetchName($request);
            if ($fetch) {
                return $this->sendResponse($fetch, 'Account Data');
            } else {
                return $this->sendError('error', 'oOps, Account data not found!!!');
            }
        }
    }

    public function driverBank(Request $request)
    {
        $driver = Auth::guard('driver_api')->user();
        $banks = DriverBank::where('driver_id', $driver->id)->get();
        return $this->sendResponse($banks, 'Driver Banks');
    }

    public function driverBankDetails(Request $request, $id)
    {
        $driver = Auth::guard('driver_api')->user();
        $bank = DriverBank::where('driver_id', $driver->id)->where('id', $id)->first();
        return $this->sendResponse($bank, 'Driver Bank Details');
    }

    public function driverEarning(Request $request)
    {
        $driver = Auth::guard('driver_api')->user();
        $earning = DriverWallet::where('driver_id', $driver->id)->first();
        $trip = TripHistory::where('driver_id', $driver->id)->sum('t_fare');
        $driver_percent = 80;
        $driver_share = ($driver_percent / 100) * $trip;
        $data = [
            'balance' => $earning->balance,
            'total_earning' => number_format($driver_share, 2)
        ];
        return $this->sendResponse($data, 'Driver Earning');
    }

    public function requestWithdraw(Request $request)
    {
        $driver = Auth::guard('driver_api')->user();
        $client = Driver::where('id', $driver->id)->first();
        $validator = Validator::make($request->all(), [
            'account_name' => 'required',
            'amount' => 'required',
            'pin' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        if ($client->pin != null) {
            if (Hash::check($request->pin, $client->pin)) {
                $check = DriverWallet::where('driver_id', $driver->id)->first();
                if ((int)$check->balance >= (int)$request->amount) {
                    $create = $this->createWithdrawTrans($request);
                    if ($create) {

                        if (isset($driver->email) && $driver->emailVerified == 1) {

                            $msg = "The sum of NGN'.$request->amount.' has been requested, your account will be credited shortly...";
                            $subject = "KoboSquare Withdrawal Notification";
                            $this->kobo->anyEmailNotification($driver->email, $driver->name, $msg, $subject);
                        }

                        return $this->sendResponse('Successful', 'The sum of NGN' . $request->amount . ' has been requested, your account will be credited shortly...');
                    } else {
                        return $this->sendError('error', 'oOps, Something went wrong!!!');
                    }
                } else {
                    return $this->sendError('error', 'oOps, You have insufficient earnings to complete this transaction');
                }
            } else {
                return $this->sendError('error', 'oOps, Invalid pin');
            }
        } else {
            return $this->sendError('error', 'oOps, Please create your pin to complete transaction');
        }
    }

    public function createWithdrawTrans($req)
    {
        $driver_id = Auth::guard('driver_api')->user()->id;
        $rw = new DriverWithrawalHistory();
        $rw->driver_id = $driver_id;
        $rw->trans_ref = 'KB-DR-' . Str::random(5) . 'RW';
        $rw->amount = $req->amount;
        $rw->account_number = $req->account_number;
        $rw->bank_name = $req->bank_name;
        $rw->account_name = $req->account_name;
        $rw->status = 'pending';
        $sv = $rw->save();

        if ($sv) {
            return true;
        } else {
            return false;
        }
    }

    public function withdrawalHistory()
    {
        $driver_id = Auth::guard('driver_api')->user()->id;
        $history = DriverWithrawalHistory::where('driver_id', $driver_id)->get();
        return $this->sendResponse($history,  'Withdrawa History!!!');
    }


    public function filterWithdrawHistory(Request $request, $status)
    {
        $driver_id = Auth::guard('driver_api')->user()->id;
        $history = DriverWithrawalHistory::where('driver_id', $driver_id)->where('status', $status)->get();
        return $this->sendResponse($history,  'Withdrawa History!!!');
    }

    public function transactDetails(Request $request, $id)
    {
        $withDetails = DriverWithrawalHistory::where('id', $id)->first();
        return $this->sendResponse($withDetails,  'Transaction Details!!!');
    }


    public function driverAvatar()
    {
        $driver_id = Auth::guard('driver_api')->user()->id;
        $image = Driver::where('id', $driver_id)->first();
        $url = 'https://drivers.kobosquare.com/driver_photo/' . $image->avatar;

        return $this->sendResponse($url, 'Driver Avatar');
    }

    public function getDriverNotification()
    {
        $client = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();
        return $this->sendResponse($client->notifications, 'User Notifications');
    }

    public function readDriverNotification($notice_id)
    {
        $client = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();
        $client->unreadNotifications->where('id', $notice_id)->markAsRead();
        return $this->sendResponse('success', 'Notification read successfully');
    }

    public function readAllDriverNotification()
    {
        $client = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();
        $client->unreadNotifications->markAsRead();
        return $this->sendResponse('success', 'All marked Notifications read successfully!!!');
    }

    public function uploadDriverAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        } else {
            if ($request->has('photo')) {
                $blfilename = Str::random(7) . '.' . $request->photo->extension();

                $request->photo->move(public_path('driver_photo'), $blfilename);


                $user = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();
                $user->avatar = $blfilename;
                $sv = $user->update();
                if ($sv) {
                    return $this->sendResponse('success', 'Driver Avatar Uploaded Successfully!!!');
                } else {
                    return $this->sendError('Error', 'oOps!, Something went wrong!!');
                }
            }
        }
    }

    public function getDriverAvatar()
    {
        $user = Driver::where('id', Auth::guard('driver_api')->user()->id)->first();

        $data = [
            'path' => 'https://api.kobosquare.com/driver_photo/',
            'avatar' => $user->avatar
        ];

        return $this->sendResponse($data, 'Driver Avatar');
    }

    public function logoutApi()
    {
        $refreshTokenRepository = app(\Laravel\Passport\RefreshTokenRepository::class);
        foreach (Driver::find(Auth::guard('driver_api')->user()->id)->tokens as $token) {
            $token->revoke();
            $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->id);
        }

        return true;
    }
}
