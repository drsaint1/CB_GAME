<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Events\BookingChatMessageCreated;
use App\Http\Controllers\Controller;
use App\Models\BookingChat;
use App\Models\BookingChatMessage;
use App\Models\Driver;
use App\Models\DriverRating;
use App\Models\DriverVehicle;
use App\Models\DriverWallet;
use App\Models\TripHistory;
use App\Models\UserWallet;
use App\Models\User;
use App\Services\BookingService;
use App\Services\KoboService;
use App\Services\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use App\Notifications\TripRequestNotification;

class BookingController extends BaseController
{
    private $booking, $notice;
    private $kobo;

    public function __construct(BookingService $booking, KoboService $kobo)
    {
        $this->booking = $booking;
        $this->kobo = $kobo;
        $this->notice = new PushNotification();
    }

    // public function bookRide(Request $request)
    // {
    //     $fromAddress = $request->from;
    //     $toAddress = $request->to;

    //     $price_per_km = 300;

    //     $getData = $this->booking->getDistance($fromAddress, $toAddress);
    //     $coordinate = $this->booking->getCoordinate($fromAddress);

    //     // Check if coordinates are obtained successfully
    //     if ($coordinate) {
    //         $latitude = $coordinate['latitude'];
    //         $longitude = $coordinate['longitude'];

    //         $driver_in_range = $this->get_driver_in_range(number_format($longitude, 6), number_format($latitude, 6));

    //         $km = $getData['rows'][0]['elements'][0]['distance']['text'];
    //         $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
    //         $from = $getData['origin_addresses'][0];
    //         $to = $getData['destination_addresses'][0];
    //         $rm_km = substr($km, 0, -3);
    //         $total_price = $price_per_km * $rm_km;
    //         $newData = [
    //             'distance' => $km,
    //             'duration' => $duration,
    //             'from_address' => $from,
    //             'to_address' => $to,
    //             'price' => $total_price,
    //             'from_long' => number_format($longitude, 6),
    //             'from_lat' => number_format($latitude, 6),
    //             'available_drivers' => $driver_in_range
    //         ];

    //         return $this->sendResponse($newData, 'Available Driver');
    //     } else {
    //         // Handle the case where coordinates couldn't be obtained
    //         return $this->sendError('Error', 'Failed to retrieve coordinates for the provided address.');
    //     }

    //     // $driver_in_range = $this->get_driver_in_range(number_format($coordinate->getLongitude(), 6), number_format($coordinate->getLatitude(), 6));
    // }


    public function bookRide(Request $request)
    {
        $fromAddress = $request->from;
        $toAddress = $request->to;

        $price_per_km_standard = 300;
        $price_per_km_luxury = 400; // Set a higher price for luxury cars

        $getData = $this->booking->getDistance($fromAddress, $toAddress);
        $coordinate = $this->booking->getCoordinate($fromAddress);

        if ($coordinate) {
            $latitude = $coordinate['latitude'];
            $longitude = $coordinate['longitude'];

            $driversInRange = $this->get_driver_in_range(number_format($longitude, 6), number_format($latitude, 6));

            $km = $getData['rows'][0]['elements'][0]['distance']['text'];
            $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
            $from = $getData['origin_addresses'][0];
            $to = $getData['destination_addresses'][0];
            $rm_km = substr($km, 0, -3);
            $total_price_standard = $price_per_km_standard * $rm_km;
            $total_price_luxury = $price_per_km_luxury * $rm_km;

            $newData = [
                'distance' => $km,
                'duration' => $duration,
                'from_address' => $from,
                'to_address' => $to,
                'available_drivers' => []
            ];

            $standard_drivers = [];
            $luxury_drivers = [];
            foreach ($driversInRange as $driver) {
                if (isset($driver['car_type'])) {
                    if ($driver['car_type'] == 'standard') {
                        $standard_drivers[] = $driver;
                    } elseif ($driver['car_type'] == 'luxury') {
                        $luxury_drivers[] = $driver;
                    }
                }
            }

            $newData['available_drivers']['standard'] = [
               'drivers' => $standard_drivers,
                'price' => $total_price_standard
            ];
            $newData['available_drivers']['luxury'] = [
               'drivers' => $luxury_drivers,
                'price' => $total_price_luxury
            ];

            return $this->sendResponse($newData, 'Available Drivers');
        } else {
            return $this->sendError('Error', 'Failed to retrieve coordinates for the provided address.');
        }
    }


    public function get_driver_in_range($latitude, $longitude, $radius = 100000)
    {
        $standardDrivers = Driver::select(
            "*",
            "driver_vehicles.id as driver_vehicle_id",
            DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                    * cos(radians(c_lat))
                    * cos(radians(c_long) - radians(" . $longitude . "))
                    + sin(radians(" . $latitude . "))
                    * sin(radians(c_lat))) AS distance")
        )
            ->where('driver_vehicles.car_type', '=', 'standard') // Filter by standard car type
            ->having("distance", "<", 100000)
            ->join('driver_vehicles', 'drivers.id', '=', 'driver_vehicles.driver_id')
            ->limit(20)
            ->get();

        $luxuryDrivers = Driver::select(
            "*",
            "driver_vehicles.id as driver_vehicle_id",
            DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                    * cos(radians(c_lat))
                    * cos(radians(c_long) - radians(" . $longitude . "))
                    + sin(radians(" . $latitude . "))
                    * sin(radians(c_lat))) AS distance")
        )
            ->where('driver_vehicles.car_type', '=', 'luxury') // Filter by luxury car type
            ->having("distance", "<", 100000)
            ->join('driver_vehicles', 'drivers.id', '=', 'driver_vehicles.driver_id')
            ->limit(20)
            ->get();

        return [
            'standard_drivers' => $standardDrivers,
            'luxury_drivers' => $luxuryDrivers
        ];
    }

    public function createTrip(){
        //dispatch to the drivers,don't input the drivers id
    }

    public function showTripDetails(){
    }


    public function driverAcceptTrip(){
        //dispatch to the users aftter driver has accepted the trip and update the trip that the driver has accepted the trip
    }



    public function tripStarted(){
        //dispatch to the users and update that trip has started
    }


    public function driversCurrentlocation(){
          //dispatch to the users and constantly update the drivers locaion trip has started
    }





    public function orderRide(Request $request)
    {

        $from_lat = $this->booking->getCoordinate($request->from_address);
        $to_lat =  $this->booking->getCoordinate($request->to_address);
        $from_long = $this->booking->getCoordinate($request->from_address);
        $to_long = $this->booking->getCoordinate($request->to_address);
        $auth_user = Auth::guard('api')->user();
        $user_id = Auth::guard('api')->user()->id;
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $pay_type = $request->payment_type;
        if ($pay_type == 'wallet') {
            $user_wallet = UserWallet::where('user_id', $user_id)->first();
            $driver_wallet = DriverWallet::where('driver_id', $request->driver_id)->first();
            $driver_percent = 80;
            $driver_share = ($driver_percent / 100) * $request->amount;

            if ((int)$user_wallet->balance < (int)$request->amount) {
                return $this->sendError('warning', 'oOps, You have insufficient wallet balance to order a ride');
            } else {

                $trip = new TripHistory();
                $trip->driver_id = $request->driver_id;
                $trip->user_id = $user_id;
                $trip->from_address = $request->from_address;
                $trip->to_address = $request->to_address;
                $trip->from_lat = number_format($from_lat['latitude'], 6);
                $trip->to_lat = number_format($to_lat['latitude'], 6);
                $trip->from_long = number_format($from_long['longitude'], 6);
                $trip->to_long = number_format($to_long['latitude'], 6);
                $trip->t_fare = $request->amount;
                $trip->payment_type = $request->payment_type;
                $trip->payment_status = 'pending';
                $trip->driver_vehicle_id = $request->driver_vehicle_id;
                $sv = $trip->save();

                if ($sv) {
                    $driver = Driver::where('id', $trip->driver_id)->first();
                    $this->notifyDriver($driver, $trip,$user);


                    $user_wallet->balance = $user_wallet->balance - $request->amount;
                    $user_wallet->update();
                    $driver_wallet->balance = $driver_wallet->balance + $driver_share;
                    $driver_wallet->update();

                    $trip->payment_status = 'processing';
                    $trip->save();


                    $id = $request->driver_id;
                    $driver = Driver::where('id', $id)->first();
                    $driver_wallet->balance = $driver_wallet->balance + $driver_share;
                    $driver_wallet->update();
                    $driverVehicle = DriverVehicle::where('driver_id', $id)->first();


                    $completed = TripHistory::where('driver_id', $request->driver_id)->where('trip_status', 'completed')->get()->count();
                    $rating = $driver->ratings()->avg('review_count');

                    $data = [
                        'trip' => $trip,
                        'driver_profile' => $driver,
                        'driver_vehicle' => $driverVehicle,
                        'driver_trip_rating' => $rating,
                        'driver_total_trip' => $completed,
                    ];

                    if (isset($auth_user->email) && $auth_user->emailVerified == 1) {
                        $msg = " you have booked a trip successfully, a driver will get back to you soon . Thanks!!!";
                        $subject = "Trip Notification";
                        $this->kobo->anyEmailNotification($auth_user->email, $auth_user->name, $msg, $subject);
                    }
                    //sending to the driver too
                    if (isset($driver->email) && $driver->emailVerified == 1) {
                        $drivermsg = " you have a trip request. Accept if you are available or decline if you are busy so we can assign trip to another driver. Thanks";
                        $driversubject = "Trip Request";
                        $this->kobo->anyEmailNotification($driver->email, $driver->name, $drivermsg, $driversubject);
                    }

                    // Check if the trip is completed
                    if ($trip->trip_status === 'completed') {
                        // Complete the trip
                        $this->completeTrip($request, $trip->id);
                    }

                    return $this->sendResponse($data, 'Trip Booked Successfully!!');
                } else {
                    return $this->sendError('Error', 'oOps, Something went wrong!!');
                }
                // $driver_wallet->balance = $driver_wallet->balance+$driver_share;
                // $driver_wallet->update();
            }
        } else {
            $trip = new TripHistory();
            $trip->driver_id = $request->driver_id;
            $trip->user_id = $user_id;
            $trip->from_address = $request->from_address;
            $trip->to_address = $request->to_address;
            $trip->from_lat = number_format($from_lat['latitude'], 6);
            $trip->to_lat = number_format($to_lat['latitude'], 6);
            $trip->from_long = number_format($from_long['longitude'], 6);
            $trip->to_long = number_format($to_long['longitude'], 6);
            $trip->t_fare = $request->amount;
            $trip->payment_type = $request->payment_type;
            $trip->driver_vehicle_id = $request->driver_vehicle_id;
            $sv = $trip->save();

            if ($sv) {
                $id = $request->driver_id;
                $driver = Driver::where('id', $id)->first();
                $driverVehicle = DriverVehicle::where('driver_id', $id)->first();
                $completed = TripHistory::where('driver_id', $request->driver_id)->where('trip_status', 'completed')->get()->count();

                $rating = $driver->ratings()->avg('review_count');

                $data = [
                    'trip' => $trip,
                    'driver_profile' => $driver,
                    'driver_vehicle' => $driverVehicle,
                    'driver_trip_rating' => $rating,
                    'driver_total_trip' => $completed
                ];

                //     $driver = Driver::find($request->driver_id);
                //     $this->notifyDriver($driver, $trip);

                //     // Check if the trip is completed
                //    if ($trip->trip_status === 'completed') {
                //       $this->deleteChat($trip->id); // Delete the chat if the trip is completed
                //     }


                if (isset($auth_user->email) && $auth_user->emailVerified == 1) {
                    $msg = "Trip Booked Successfully!!";
                    $subject = "Kobosquare Trip Notification";
                    $this->kobo->anyEmailNotification($auth_user->email, $auth_user->name, $msg, $subject);
                }

                return $this->sendResponse($data, 'Trip Booked Successfully!!');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }


    private function notifyDriver($driver, $trip,$user)
    {
        $driverMessage = 'Hello, ' . $driver->first_name . ' ' . $driver->surname .' you have a trip request. Accept if you are available or decline if you are busy so we can assign trip to another driver. Thanks';

        $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';

        // Send push notifications
        // $this->notice->sendPushNotification("01", 'Trip Request', $driverMessage, [$driver->token], null, $trip);
        // $this->notice->sendPushNotification("01", 'Order Trip', $userMessage, [$user->token], null, $trip);
        // // Send push notifications
        // $driver->notify(new TripRequestNotification($driverMessage, $trip));
        // $trip->user->notify(new TripRequestNotification($userMessage, $trip));
    }


    public function completeTrip(Request $request, $tripId)
    {
        $trip = TripHistory::find($tripId);
        if ($trip) {
            // Mark the trip as completed
            $trip->payment_status = 'paid';
            $trip->trip_status = 'completed';
            $trip->save();

            // Delete the chat associated with the completed trip
            $this->deleteChat($tripId);

            return $this->sendResponse([], 'Trip completed successfully');
        } else {
            return $this->sendError('error', 'Trip not found');
        }
    }

    private function deleteChat($tripId)
    {
        $completedTrip = TripHistory::where('id', $tripId)->where('trip_status', 'completed')->first();
        if ($completedTrip) {
            $chat = BookingChat::where('trip_history_id', $tripId)->first();
            if ($chat) {
                // Delete chat messages
                BookingChatMessage::where('booking_chat_id', $chat->id)->delete();

                // Delete chat itself
                $chat->delete();

                return $this->sendResponse([], 'Chat deleted successfully');
            } else {
                return $this->sendError('error', 'Chat not found');
            }
        } else {
            return $this->sendError('error', 'Trip is not completed');
        }
    }


    // public function get_driver_in_range($latitude, $longitude, $radius = 100000)
    // {
    //     // $drivers = Driver::selectRaw(" *, driver_vehicles.id as vehicle_id,
    //     //                 ( 6371 * acos( cos( radians(?) ) *
    //     //                 cos( radians( c_lat ) )
    //     //                 * cos( radians( c_long ) - radians(?)
    //     //                 ) + sin( radians(?) ) *
    //     //                 sin( radians( c_lat ) ) )
    //     //                 ) AS distance", [$latitude, $longitude, $latitude])
    //     //     ->having("distance", "<=", $radius)

    //     $drivers = Driver::select(
    //         "*",
    //         "driver_vehicles.id as driver_vehicle_id",
    //         DB::raw("6371 * acos(cos(radians(" . $latitude . "))
    //                     * cos(radians(c_lat))
    //                     * cos(radians(c_long) - radians(" . $longitude . "))
    //                     + sin(radians(" . $latitude . "))
    //                     * sin(radians(c_lat))) AS distance")
    //     )
    //         ->having("distance", "<", 100000)
    //         ->join('driver_vehicles', 'drivers.id', '=', 'driver_vehicles.driver_id')
    //         ->limit(20)
    //         ->get();

    //     return $drivers;
    // }

    // public function orderRide(Request $request)
    // {

    //     $from_lat = $this->booking->getCoordinate($request->from_address);
    //     $to_lat =  $this->booking->getCoordinate($request->to_address);
    //     $from_long = $this->booking->getCoordinate($request->from_address);
    //     $to_long = $this->booking->getCoordinate($request->to_address);
    //     $auth_user = Auth::guard('api')->user();
    //     $user_id = Auth::guard('api')->user()->id;
    //     $user = User::where('id', Auth::guard('api')->user()->id)->first();
    //     $pay_type = $request->payment_type;
    //     if ($pay_type == 'wallet') {
    //         $user_wallet = UserWallet::where('user_id', $user_id)->first();
    //         $driver_wallet = DriverWallet::where('driver_id', $request->driver_id)->first();
    //         $driver_percent = 80;
    //         $driver_share = ($driver_percent / 100) * $request->amount;

    //         if ((int)$user_wallet->balance < (int)$request->amount) {
    //             return $this->sendError('warning', 'oOps, You have insufficient wallet balance to order a ride');
    //         } else {
    //             $user_wallet->balance = $user_wallet->balance - $request->amount;
    //             $user_wallet->update();
    //             $driver_wallet->balance = $driver_wallet->balance + $driver_share;
    //             $driver_wallet->update();
    //             $trip = new TripHistory();
    //             $trip->driver_id = $request->driver_id;
    //             $trip->user_id = $user_id;
    //             $trip->from_address = $request->from_address;
    //             $trip->to_address = $request->to_address;
    //             $trip->from_lat = number_format($from_lat['latitude'], 6);
    //             $trip->to_lat = number_format($to_lat['latitude'], 6);
    //             $trip->from_long = number_format($from_long['longitude'], 6);
    //             $trip->to_long = number_format($to_long['latitude'], 6);
    //             $trip->t_fare = $request->amount;
    //             $trip->payment_type = $request->payment_type;
    //             $trip->payment_status = 'pending';
    //             $trip->driver_vehicle_id = $request->driver_vehicle_id;
    //             $sv = $trip->save();

    //             if ($sv) {
    //                 $driver = Driver::where('id', $trip->driver_id)->first();
    //                 $this->notice->sendPushNotification("01", 'Trip Request', 'Hello, ' . $driver->first_name . ' ' . $driver->surname . ' you have a trip request. Accept if you are available or decline if you are busy so we can assign trip to another driver. Thanks', array($driver->token), null, $trip);
    //                 $this->notice->sendPushNotification("01", 'Order Trip', 'Hello ' . $user->username . ',' . ' you have booked a trip successfully, a driver will get back to you soon . Thanks', array($user->token), null, $trip);
    //                 $id = $request->driver_id;
    //                 $driver = Driver::where('id', $id)->first();
    //                 $driver_wallet->balance = $driver_wallet->balance + $driver_share;
    //                 $driver_wallet->update();
    //                 $driverVehicle = DriverVehicle::where('driver_id', $id)->first();
    //                 // $trip_count = TripHistory::where('trip_status', 'cancelled')->where('trip_status', 'completed')->get()->count();
    //                 // $cancel = TripHistory::where('trip_status', 'cancelled')->get()->count();
    //                 $completed = TripHistory::where('driver_id', $request->driver_id)->where('trip_status', 'completed')->get()->count();
    //                 // $cancel_total = $cancel / $trip * 100;
    //                 // $complete_total = $completed / $trip * 100;

    //                 $rating = $driver->ratings()->avg('review_count');

    //                 $data = [
    //                     'trip' => $trip,
    //                     'driver_profile' => $driver,
    //                     'driver_vehicle' => $driverVehicle,
    //                     'driver_trip_rating' => $rating,
    //                     'driver_total_trip' => $completed,
    //                 ];

    //                 if (isset($auth_user->email) && $auth_user->emailVerified == 1) {
    //                     $msg = " you have booked a trip successfully, a driver will get back to you soon . Thanks!!!";
    //                     $subject = "Trip Notification";
    //                     $this->kobo->anyEmailNotification($auth_user->email, $auth_user->name, $msg, $subject);
    //                 }
    //                 //sending to the driver too
    //                 if (isset($driver->email) && $driver->emailVerified == 1) {
    //                     $drivermsg = " you have a trip request. Accept if you are available or decline if you are busy so we can assign trip to another driver. Thanks";
    //                     $driversubject = "Trip Request";
    //                     $this->kobo->anyEmailNotification($driver->email, $driver->name, $drivermsg, $driversubject);
    //                 }

    //                 return $this->sendResponse($data, 'Trip Booked Successfully!!');
    //             } else {
    //                 return $this->sendError('Error', 'oOps, Something went wrong!!');
    //             }
    //             // $driver_wallet->balance = $driver_wallet->balance+$driver_share;
    //             // $driver_wallet->update();
    //         }
    //     } else {
    //         $trip = new TripHistory();
    //         $trip->driver_id = $request->driver_id;
    //         $trip->user_id = $user_id;
    //         $trip->from_address = $request->from_address;
    //         $trip->to_address = $request->to_address;
    //         $trip->from_lat = number_format($from_lat['latitude'], 6);
    //         $trip->to_lat = number_format($to_lat['latitude'], 6);
    //         $trip->from_long = number_format($from_long['longitude'], 6);
    //         $trip->to_long = number_format($to_long['longitude'], 6);
    //         $trip->t_fare = $request->amount;
    //         $trip->payment_type = $request->payment_type;
    //         $trip->driver_vehicle_id = $request->driver_vehicle_id;
    //         $sv = $trip->save();

    //         if ($sv) {
    //             $id = $request->driver_id;
    //             $driver = Driver::where('id', $id)->first();
    //             $driverVehicle = DriverVehicle::where('driver_id', $id)->first();
    //             $completed = TripHistory::where('driver_id', $request->driver_id)->where('trip_status', 'completed')->get()->count();

    //             $rating = $driver->ratings()->avg('review_count');

    //             $data = [
    //                 'trip' => $trip,
    //                 'driver_profile' => $driver,
    //                 'driver_vehicle' => $driverVehicle,
    //                 'driver_trip_rating' => $rating,
    //                 'driver_total_trip' => $completed
    //             ];

    //             if (isset($auth_user->email) && $auth_user->emailVerified == 1) {
    //                 $msg = "Trip Booked Successfully!!";
    //                 $subject = "Kobosquare Trip Notification";
    //                 $this->kobo->anyEmailNotification($auth_user->email, $auth_user->name, $msg, $subject);
    //             }

    //             return $this->sendResponse($data, 'Trip Booked Successfully!!');
    //         } else {
    //             return $this->sendError('Error', 'oOps, Something went wrong!!');
    //         }
    //     }
    // }

    public function rateDriver(Request $request)
    {
        $check_trip = TripHistory::where('id', $request->trip_id)->first();
        if ($check_trip->trip_status != 'completed') {
            return $this->sendError('warning', 'oOps, Trip is still ongoing');
        } else if ($check_trip->payment_status != 'paid') {
            return $this->sendError('warning', 'oOps, You can\'t review this trip until you make payment');
        } else {
            $user_id = Auth::guard('api')->user()->id;
            $rating = new DriverRating();
            $rating->trip_history_id = $request->trip_id;
            $rating->driver_id = $request->driver_id;
            $rating->user_id = $user_id;
            $rating->review_count = $request->review_count;
            $rating->review = $request->review;
            $sv = $rating->save();
            if ($sv) {
                return $this->sendResponse($rating, "Review submitted successfully");
            } else {
                return $this->sendError('error', 'oOps!, Something went wong!!');
            }
        }
    }


    public function getRides(){
        $user = Auth::guard('api')->user();
        $trips = TripHistory::where('user_id', $user->id)->get();
        if (!$trips) {
            return $this->sendError('error', 'you have not order any trip');
        }else{
            return $this->sendResponse($trips, "ordered trips");
        }
    }

    public function cancelRide(Request $request)
    {
        $id = Auth::guard('api')->user()->id;
        $trip = TripHistory::where('id', $request->trip_id)->where('user_id', $id)->first();
        if ($trip->trip_status == 'pending' || $trip->trip_status == 'completed' || $trip->trip_status == 'cancelled' || $trip->trip_status == 'decline') {
            return $this->sendError('Error', 'Trip cannot be cancelled');
        } else {
            $trip->trip_status = 'cancelled';
            $tu = $trip->update();
            if ($tu) {

                return $this->sendResponse($trip, 'Ride Cancelled');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }

    // public function getDeliveries(){
    //     $user = Auth::guard('api')->user();
    //     $history = DeliveryHistory::where('users_id', $user->id)->get();
    //     if (!$history) {
    //         return $this->sendError('error', 'you have not order any trip');
    //     }else{
    //         return $this->sendResponse($history, "ordered trips");
    //     }
    // }

    public function replyChat(Request $request, $driverid)
    {
        $userid = Auth::guard('api')->user()->id;
        $chat = BookingChat::where('trip_history_id', $request->trip_id)->where('driver_id', $userid)->first();


        if (!$chat) {
            // Create a new chat if it doesn't exist
            $newChat = new BookingChat();
            $newChat->trip_history_id = $request->trip_id;
            $newChat->user_id = $userid;
            $newChat->driver_id = $driverid;
            $newChat->message = $request->text;
            $newChat->save();

            $chat = $newChat;
        } else {
            $new = new BookingChatMessage();
            $new->booking_chat_id = $chat->id;
            $new->user_entity_id = $userid;
            $new->text = $request->text;
            $sv = $new->save();

            if ($sv) {
                event(new BookingChatMessageCreated($new));

                $driver = Driver::where('id', $driverid)->first();
                $this->notice->sendPushNotification("01", 'Trip Message', $request->text, array($driver->token), null, $new);

                return $this->sendResponse($new, 'Message Sent');
            } else {
                return $this->sendError('error', 'Oops, Something wend wring!');
            }
        }
        // } else {
        //     return $this->sendError('error', 'Oops, Chat not found!');
        // }
    }

    public function bookingChats(Request $request, $trip_id)
    {
        $chat = BookingChat::where('trip_history_id', $trip_id)->first();
        $chat_message = BookingChatMessage::where('booking_chat_id', $chat->id)->get();
        return $this->sendResponse($chat_message, "Chat Messages");
    }

    public function getTrips()
    {
        $id = Auth::guard('api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->get();
        $trips = DB::table('trip_histories')->select('trip_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'trip_histories.user_id', '=', 'users.id')
            ->join('drivers', 'trip_histories.driver_id', '=', 'drivers.id')
            ->where('user_id', $id)
            ->get();
        return $this->sendResponse($trips, 'User trips');
    }

    public function getTrip($trip_id)
    {
        $id = Auth::guard('api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->get();
        $trip = DB::table('trip_histories')->select('trip_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'trip_histories.user_id', '=', 'users.id')
            ->join('drivers', 'trip_histories.driver_id', '=', 'drivers.id')
            ->where('user_id', $id)
            ->where('trip_histories.id', $trip_id)
            ->first();
        return $this->sendResponse($trip, 'User trips');
    }
}
