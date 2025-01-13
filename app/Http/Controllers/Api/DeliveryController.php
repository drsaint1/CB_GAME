<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Events\BookingChatMessageCreated;
use App\Events\TripCreated;
use App\Events\TripStarted;
use App\Events\TripAccepted;
use App\Events\TripEnded;
use App\Events\TripRequest;
use App\Events\TripCanceled;
use App\Events\DriverArrived;
use App\Events\DeliveryRequest;
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
use Illuminate\Support\Facades\Validator;
use App\Events\DriverLocationUpdated;
use App\Models\Rider;
use App\Models\RiderRating;
use App\Models\DeliveryHistory;
use App\Enums\DeliveryPriceEnum;
use App\Enums\RiderAvailStatusEnum;
use App\Enums\WalletDescEnum;
use App\Models\VehicleType;
use App\Services\WalletService;
// use App\Notifications\TripRequestNotification;


class DeliveryController extends BaseController
{


    private $booking, $notice;
    private $kobo;

    public function __construct(BookingService $booking, KoboService $kobo)
    {
        $this->booking = $booking;
        $this->kobo = $kobo;
        $this->notice = new PushNotification();
    }



    public function get_rider_in_range($latitude, $longitude, $vehicleType, $radius = 100000)
{
    $riders = Rider::select(
            "riders.*",
            "logistic_vehicles.id as vehicle_vehicle_id",
            DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                        * cos(radians(c_lat))
                        * cos(radians(c_long) - radians(" . $longitude . "))
                        + sin(radians(" . $latitude . "))
                        * sin(radians(c_lat))) AS distance")
        )
        ->join('logistic_vehicles', 'riders.logistic_vehicles_id', '=', 'logistic_vehicles.id')
        ->where('riders.availability', 'available')
        ->where('riders.status', 'approved')
        ->where('logistic_vehicles.vehicle_types_id', $vehicleType)
        ->having("distance", "<", $radius)
        ->offset(0)
        ->limit(20)
        ->get();

    return $riders;
}


    // public function get_rider_in_range($latitude, $longitude, $vehicleType, $radius = 100000)
    // {
    //     $riders = Rider::select(
    //         "*",
    //         "logistic_vehicles.id as vechicle_vehicle_id",
    //         DB::raw("6371 * acos(cos(radians(" . $latitude . "))
    //                     * cos(radians(c_lat))
    //                     * cos(radians(c_long) - radians(" . $longitude . "))
    //                     + sin(radians(" . $latitude . "))
    //                     * sin(radians(c_lat))) AS distance")
    //     )
    //     ->where('riders.availability', '=', 'available')
    //         ->where('riders.status', 'approved')
    //        ->where('logistic_vehicles.vehicle_types_id', $vehicleType)
    //        ->having("distance", "<", $radius)
    //         ->offset(0)
    //        ->limit(20)
    //         ->get();
    //         // ->having("distance", "<", 100000)
    //         // // ->join('driver_vehicles', 'drivers.id', '=', 'driver_vehicles.driver_id')
    //         // ->join('logistic_vehicles','riders.logistic_vehicles_id','=','logistic_vehicles.id')
    //         // ->where('riders.availability', 'available') // Filter by availability status
    //         // ->limit(20)
    //         // ->get();

    //     return $riders;
    // }

    // another one
    // public function getRiderInRange($latitude, $longitude, $vehicleType, $radius = 80){
    //     $riders = Rider::selectRaw(" *,
    //                     ( 6371 * acos( cos( radians(?) ) *
    //                     cos( radians( c_lat ) )
    //                     * cos( radians( c_long ) - radians(?)
    //                     ) + sin( radians(?) ) *
    //                     sin( radians( c_lat ) ) )
    //                     ) AS distance", [$latitude, $longitude, $latitude])
    //         ->where('riders.availability', '=', 'available')
    //         ->where('riders.status', 'approved')
    //         ->where('logistic_vehicles.vehicle_types_id', $vehicleType)
    //         ->having("distance", "<", $radius)
    //         ->offset(0)
    //         ->limit(20)
    //         ->get();
    //     return $riders;
    // }

    public function bookDelivery(Request $request)
    {
        $fromAddress = $request->from;
        $toAddress = $request->to;

        $price_per_km_lite = 200;
        $price_per_km_fragile = 300; // Set a higher price for luxury cars
        $price_per_km_heavy = 400;

        $getData = $this->booking->getDistance($fromAddress, $toAddress);
        // $coordinate = $this->booking->getCoordinate($fromAddress);

        if ($getData) {

            $km = $getData['rows'][0]['elements'][0]['distance']['text'];
            $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
            $from = $getData['origin_addresses'][0];
            $to = $getData['destination_addresses'][0];
            $rm_km = substr($km, 0, -3);
            $total_price_lite = $price_per_km_lite * $rm_km;
            $total_price_fragile = $price_per_km_fragile * $rm_km;
            $total_price_heavy = $price_per_km_heavy * $rm_km;



            $price = [
                'lite_price' =>  $total_price_lite,
                'fragile_price' =>  $total_price_fragile,
                'heavy_price' => $total_price_heavy
            ];

            $newData = [
                'distance' => $km,
                'duration' => $duration,
                'from_address' => $from,
                'to_address' => $to,
                'price' => $price
            ];
            return $this->sendResponse($newData, 'ride details');
        } else {
            return $this->sendError('Error', 'Failed to retrieve distance for the provided address.');
        }
    }


    // //avialable drivers in range.
    // public function availableDrivers(Request $request)
    // {
    //     $fromAddress = $request->from;
    //     $coordinate = $this->booking->getCoordinate($fromAddress);

    //     if ($coordinate) {
    //         $latitude = $coordinate['latitude'];
    //         $longitude = $coordinate['longitude'];

    //         $standard_drivers = [];
    //         $luxury_drivers = [];

    //         $driversInRange = $this->get_driver_in_range(number_format($longitude, 6), number_format($latitude, 6));

    //         foreach ($driversInRange as $driver) {
    //             if (isset($driver['car_type'])) {
    //                 if ($driver['car_type'] == 'standard') {
    //                     $standard_drivers[] = $driver;
    //                 } elseif ($driver['car_type'] == 'luxury') {
    //                     $luxury_drivers[] = $driver;
    //                 }
    //             }
    //         }
    //         $drivers = [
    //             'standard_drivers' => $standard_drivers,
    //             'luxury_drivers' => $luxury_drivers,
    //         ];

    //         return $this->sendResponse($drivers, 'Available Drivers');
    //     } else {
    //         return $this->sendError('Error', 'Failed to retrieve coordinates for the provided address.');
    //     }
    // }


    //create a trip or order a ride
    public function createDelivery(Request $request)
    {
        $from_lat = $this->booking->getCoordinate($request->from_address);
        $to_lat =  $this->booking->getCoordinate($request->to_address);
        $from_long = $this->booking->getCoordinate($request->from_address);
        $to_long = $this->booking->getCoordinate($request->to_address);

        $user_id = Auth::guard('api')->user()->id;
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $pay_type = $request->payment_type;

        $car_type = $request->car_type;
        if (!in_array($car_type, ['lite', 'heavy','fragile'])) {
            return $this->sendError('Error', 'Invalid package type selected.');
        }

        // Find drivers in range with the selected car type
        $ridersInRange = $this->get_rider_in_range(number_format($from_lat['latitude'], 6), number_format($from_long['longitude'], 6),$car_type);
        $selectedRiders = [];
        foreach ($ridersInRange as $rider) {
            if (isset($rider['type']) && $rider['type'] == $car_type && $rider['availability'] == 'available') {
                $selectedRiders[] = $rider;
            }
        }

        if ($pay_type == 'wallet') {
            $user_wallet = UserWallet::where('user_id', $user_id)->first();

            if ((int)$user_wallet->balance < (int)$request->amount) {
                return $this->sendError('warning', 'oOps, You have insufficient wallet balance to order a ride');
            } else {

                $deliveryTrip = new DeliveryHistory();
                // $deliveryTrip->riders_id = $request->rider_id;
                $deliveryTrip->users_id = $user_id;
                $deliveryTrip->from_address = $request->origins_address;
                $deliveryTrip->to_address = $request->destination_address;
                $deliveryTrip->from_lat = number_format($from_lat['latitude'], 6);
                $deliveryTrip->to_lat = number_format($to_lat['latitude'], 6);
                $deliveryTrip->from_long =  number_format($from_long['longitude'], 6);
                $deliveryTrip->to_long =number_format($to_long['latitude'], 6);
                $deliveryTrip->t_fare = $request->amount;
                // $deliveryTrip->logistic_vehicles_id = $rider->logistic_vehicles_id;
                $deliveryTrip->payment_type = $request->payment_type;
                $deliveryTrip->car_type = $request->car_type;
                $deliveryTrip->payment_status = 'pending';
                $saveTrip = $deliveryTrip->save();

                if ($saveTrip) {
                    $details = [
                        "user" => $user,
                        "ride" => $deliveryTrip,
                    ];


                    $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';
                    $this->notice->sendPushNotification("01", 'Order Trip', $userMessage, [$user->token], null, $details);

                    event(new DeliveryRequest($details, $selectedRiders));
                    // Send push notifications to selected drivers
                    foreach ($selectedRiders as $rider) {
                        $riderUser = rider::find($rider['id']); // Assuming rider['id'] is the ID of the rider
                        if ($riderUser) {
                            $riderMessage = 'Hello ' . $riderUser->username . ', you have a trip request. Please accept the trip.';
                            $this->notice->sendPushNotification("01", 'Trip Request', $riderMessage, [$riderUser->token], null, $details);
                        }
                    }

                    return $this->sendResponse($selectedRiders, 'order a ride Successful , searching for drivers');
                } else {
                    return $this->sendError('Error', 'oOps, Something went wrong!!');
                }
            }
        } else {
            $deliveryTrip = new DeliveryHistory();
            // $deliveryTrip->riders_id = $request->rider_id;
            $deliveryTrip->users_id = $user_id;
            $deliveryTrip->from_address = $request->origins_address;
            $deliveryTrip->to_address = $request->destination_address;
            $deliveryTrip->from_lat = number_format($from_lat['latitude'], 6);
            $deliveryTrip->to_lat = number_format($to_lat['latitude'], 6);
            $deliveryTrip->from_long =  number_format($from_long['longitude'], 6);
            $deliveryTrip->to_long =number_format($to_long['latitude'], 6);
            $deliveryTrip->t_fare = $request->amount;
            $deliveryTrip->logistic_vehicles_id = $rider->logistic_vehicles_id;
            $deliveryTrip->payment_type = $request->payment_type;
            $deliveryTrip->car_type = $request->car_type;
            $deliveryTrip->payment_status = 'pending';
            $saveTrip = $deliveryTrip->save();

            if ($saveTrip) {
                $details = [
                    "user" => $user,
                    "deliveryTrip" => $deliveryTrip,
                ];
                // Send push notifications
                $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';
                $this->notice->sendPushNotification("01", 'Order Trip', $userMessage, [$user->token], null, $deliveryTrip);

                $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';
                    $this->notice->sendPushNotification("01", 'Order Trip', $userMessage, [$user->token], null, $details);

                    event(new DeliveryRequest($details, $selectedRiders));
                    // Send push notifications to selected drivers
                    foreach ($selectedRiders as $rider) {
                        $riderUser = rider::find($rider['id']); // Assuming rider['id'] is the ID of the rider
                        if ($riderUser) {
                            $riderMessage = 'Hello ' . $riderUser->username . ', you have a trip request. Please accept the trip.';
                            $this->notice->sendPushNotification("01", 'Trip Request', $riderMessage, [$riderUser->token], null, $details);
                        }
                    }

                    return $this->sendResponse($selectedRiders, 'order a ride Successful , searching for drivers');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
        //dispatch to the drivers,don't input the drivers id
    }


    // public function  riderAcceptDeliveryTrip(Request $request, $tripId)
    // {
    //     $rider = Rider::where('id', $request->rider_id)->first();
    //     if(!$rider){
    //         return $this->sendError('error', 'the rider does not exist');
    //     }

    //     // $riderVehicle = $rider->logistic_vehicles;
    //     $driverVehicle = DriverVehicle::where('driver_id', $request->rider_id)->first();
    //     $delivery = DeliveryHistory::where('id', $tripId)->first();
    //     $completed =    DeliveryHistory::where('riders_id', $rider->id)->where('delivery_status', 'completed')->count();
    //     // $rating = $riders->ratings()->avg('review_count');
    //     $user = User::where('id', $delivery->users_id)->first();
    //     if ($delivery->delivery_status == 'accept') {
    //         return $this->sendError('Error', 'delivery has been accepted by another driver/not available/does\'nt exist');
    //     } else {
    //         // Update the existing trip instance with driver_id and driver_location
    //         $delivery->delivery_status = 'accept';
    //         $delivery->rider_id = $request->rider_id;
    //         $delivery->rider_location = $request->rider_location;
    //         $sv = $delivery->save();

    //         $this->driverInitiateTripChat($delivery->id);

    //         if ($sv) {
    //             $data = [
    //                 'delivery' => $delivery,
    //                 'rider_profile' => $rider,
    //                 'driver_vehicle' => $driverVehicle,
    //                 // 'driver_trip_rating' => $rating,
    //                 'driver_total_trip' => $completed,
    //             ];
    //             // Dispatch the event after updating the trip
    //             event(new TripAccepted($data, $user));

    //             $userMessage = 'Hi ' . $user->username . ', your trip order request have been accepted and you driver is on the way, please wait for him.';
    //             $this->notice->sendPushNotification("01", 'Trip Accepted', $userMessage, [$user->token], null, $delivery);
    //             $this->notice->sendPushNotification("01", 'Trip Accepted', 'Hi, you just accepted a trip order', array($rider->token), null, $delivery);

    //             return $this->sendResponse($data, 'Trip Booked Successfully!!');
    //         }
    //     }
    // }

    // public function riderHasArrived(Request $request, $deliveryId)
    // {
    //     // // $id = $request->driver_id;
    //     // $id = Auth::guard('driver_api')->user()->id;
    //     $rider = Rider::where('id', $request->rider_id)->first();
    //     if(!$rider){
    //         return $this->sendError('error', 'the rider does not exist');
    //     }

    //     $delivery = DeliveryHistory::where('id', $deliveryId)->where('riders_id', $request->rider_id)->first();
    //     $user = User::where('id', $delivery->users_id)->first();

    //     $details = [
    //         "rider" => $rider,
    //         "delivery" => $delivery
    //     ];

    //     event(new DriverArrived($details, $user));

    //     $userMessage = 'Hi ' . $user->username . ', your rider has arrived, your delivery will start soon.';
    //     $this->notice->sendPushNotification("01", 'Driver has Arrived', $userMessage, [$user->token], null, $delivery);
    //     $this->notice->sendPushNotification("01", 'User Notified', 'take time to find your user', array($rider->token), null, $delivery);

    //     return $this->sendResponse($details, 'user notified,rider has arrived');
    // }



    // public function tripStarted(Request $request, $deliveryId)
    // {
    //     $delivery = DeliveryHistory::find($deliveryId);
    //     if ($delivery) {

    //         $rider = Rider::where('id', $request->rider_id)->first();
    //         if(!$rider){
    //             return $this->sendError('error', 'the rider does not exist');
    //         }

    //         $user = User::where('id', $delivery->users_id)->first();
    //         $pay_type = $delivery->payment_type;

    //         if ($pay_type == 'wallet') {
    //             $user_wallet = UserWallet::where('user_id', $delivery->users_id)->first();
    //     //  logisitics wallet
    //             $driver_wallet = DriverWallet::where('driver_id', $delivery->driver_id)->first();
    //             $driver_percent = 80;
    //             $driver_share = ($driver_percent / 100) * $delivery->t_fare;

    //             if ((int)$user_wallet->balance < (int)$delivery->t_fare) {
    //                 return $this->sendError('warning', 'oOps, You have insufficient wallet balance to order a ride');
    //             } else {
    //                 $driver = Driver::where('id', $delivery->driver_id)->first();

    //                 $user_wallet->balance = $user_wallet->balance - $delivery->t_fare;
    //                 $user_wallet->update();
    //             //
    //                 $driver_wallet->balance = $driver_wallet->balance + $driver_share;
    //                 $driver_wallet->update();

    //                 $delivery->payment_status = 'pending';
    //                 $delivery->is_started = true;
    //                 $sv = $delivery->save();

    //                 if ($sv) {
    //                     $details = [
    //                         "driver" => $driver,
    //                         "trip" => $delivery,
    //                         "user" => $user
    //                     ];
    //                     $userMessage = 'Hello ' . $user->username . ', your trip has started to drive you will be taken to you destination';
    //                     $this->notice->sendPushNotification("01", 'Trip Started', $userMessage, [$user->token], null, $delivery);
    //                     // Dispatch the event after updating the trip
    //                     event(new TripStarted($details, $user));
    //                     // Delete the chat associated with the completed trip
    //                     $this->deleteChat($deliveryId);
    //                     return $this->sendResponse($details, 'Trip has started');
    //                 }
    //             }
    //         }
    //     } else {
    //         return $this->sendError('error', 'delivery not found');
    //     }
    // }


    // public function tripComplete(Request $request, $deliveryId)
    // {
    //     $delivery = DeliveryHistory::find($deliveryId);
    //     if ($delivery) {

    //         $rider = Rider::where('id', $request->rider_id)->first();
    //         if(!$rider){
    //             return $this->sendError('error', 'the rider does not exist');
    //         }
    //         $user = User::where('id', $delivery->user_id)->first();
    //         $delivery->payment_status = 'paid';
    //         $delivery->delivery_status = 'completed';
    //         $delivery->is_complete = true;
    //         $sv = $delivery->save();


    //         if ($sv) {
    //             $details = [
    //                 "rider" => $rider,
    //                 "delivery" => $delivery,
    //                 "user" => $user
    //             ];
    //             // Dispatch the event after updating the delivery
    //             event(new deliveryEnded($details, $user));

    //             $userMessage = 'Hello ' . $user->username . ', your delivery is now completed';
    //             $this->notice->sendPushNotification("01", 'delivery Started', $userMessage, [$user->token], null, $delivery);

    //             if (isset($user->email) && $user->emailVerified == 1) {
    //                 $msg = "your ride was complete, give the rider a rating!!";
    //                 $subject = "Kobosquare delivery Completed";
    //                 $this->kobo->anyEmailNotification($user->email, $user->name, $msg, $subject);
    //             }
    //             // Delete the chat associated with the completed delivery
    //             $this->deleteChat($deliveryId);
    //             return $this->sendResponse($details, 'delivery is completed');
    //         }
    //     } else {
    //         return $this->sendError('error', 'delivery not found');
    //     }
    // }


    // public function driversCurrentlocation()
    // {
    //     //dispatch to the users and constantly update the drivers locaion trip has started
    // }

    // public function updateDriverLocation(Request $request, $riderId)
    // {
    //     // Validate the incoming request data
    //     $validator = Validator::make($request->all(), [
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     $rider = Rider::find($riderId);
    //     if (!$rider) {
    //         return response()->json(['error' => 'rider not found'], 404);
    //     }

    //     $rider->c_lat = $request->latitude;
    //     $rider->c_long = $request->longitude;
    //     $rider->save();

    //     event(new riderLocationUpdated($riderId, $request->latitude, $request->longitude));

    //     return response()->json(['message' => 'Rider location updated successfully'], 200);
    // }


    // public function cancelTrip(Request $request)
    // {
    //     // $id = Auth::guard('driver_api')->user()->id;
    //     $rider = Rider::where('id', $request->rider_id)->first();
    //     if(!$rider){
    //         return $this->sendError('error', 'the rider does not exist');
    //     }

    //     $trip = DeliveryHistory::where('id', $request->delivery_id)->where('riders_id', $request->rider_id)->first();
    //     if ($trip->trip_status == 'pending' || $trip->trip_status == 'completed' || $trip->trip_status == 'cancelled' || $trip->trip_status == 'decline') {
    //         return $this->sendError('Error', 'Trip cannot be cancelled');
    //     } else {
    //         $trip->trip_status = 'cancelled';
    //         $tu = $trip->update();
    //         if ($tu) {
    //             event(new TripCanceled($trip, $trip->user_id));
    //             return $this->sendResponse($trip, 'delivery Cancelled');
    //         } else {
    //             return $this->sendError('Error', 'oOps, Something went wrong!!');
    //         }
    //     }
    // }




    public function driverInitiateTripChat($trip_id)
    {
        $init = new BookingChat();
        $init->trip_history_id = $trip_id;
        $sv = $init->save();
        return $sv;
    }



    // public function driverReplyChat(Request $request, $userid)
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



    public function driverdDeclineTrip(Request $request)
    {
        $id = Auth::guard('driver_api')->user()->id;
        $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->where('trip_status', 'pending')->first();
        if ($trip->trip_status != 'pending') {
            return $this->sendError('Error', 'Trip has been accepted by another driver/not available/does\'nt exist');
        } else {
            $trip->trip_status = 'decline';
            $tu = $trip->update();
            if ($tu) {
                return $this->sendResponse($trip, 'Trip Declined successfully');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }







    private function notifyDriver($driver, $trip, $user)
    {
        $driverMessage = 'Hello, ' . $driver->first_name . ' ' . $driver->surname .
            ' you have a trip request. Accept if you are available or decline if you are busy so we can assign trip to another driver. Thanks';

        $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';
    }


    // public function completeTrip(Request $request, $tripId)
    // {
    //     $trip = TripHistory::find($tripId);
    //     if ($trip) {
    //         // Mark the trip as completed
    //         $trip->payment_status = 'paid';
    //         $trip->trip_status = 'completed';
    //         $trip->save();

    //         // Delete the chat associated with the completed trip
    //         $this->deleteChat($tripId);

    //         return $this->sendResponse([], 'Trip completed successfully');
    //     } else {
    //         return $this->sendError('error', 'Trip not found');
    //     }
    // }

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


    public function rateRider(Request $request){
        $request->validate([
            'trip_id' => 'required'
        ]);

        $check_trip = DeliveryHistory::where('id', $request->trip_id)->first();
        if($check_trip->delivery_status != 'completed'){
            return $this->sendError('warning', 'Oops, Trip is still ongoing');
        }
        else if($check_trip->payment_status != 'paid'){
            return $this->sendError('warning', 'Oops, You can\'t review this trip until you make payment');
        }else{
            $user_id = Auth::guard('api')->user()->id;
            $rating = new RiderRating();
            $rating->delivery_history_id = $request->trip_id;
            $rating->riders_id = $request->rider_id;
            $rating->user_id = $user_id;
            $rating->review_count = $request->review_count;
            $rating->review = $request->review;
            $sv = $rating->save();
            if($sv){
                return $this->sendResponse($rating, "Review submitted successfully");
            }else{
                return $this->sendError('error', 'Oops!, Something went wong!!');
            }
        }
    }



    public function getRides()
    {
        $user = Auth::guard('api')->user();
        $trips = TripHistory::where('user_id', $user->id)->get();
        if (!$trips) {
            return $this->sendError('error', 'you have not order any trip');
        } else {
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

    public function deliveryHistory(Request $request){
        $id = Auth::guard('api')->user()->id;
        $history = DeliveryHistory::paginate()->where('users_id', $id);

        return $this->sendResponse($history, 'Success');
    }

    public function getTrips()
    {
        $id = Auth::guard('api')->user()->id;
        //$trips = TripHistory::where('driver_id', $id)->get();
        $deliverys = DB::table('delivery_histories')->select('delivery_histories.*', 'users.name as user_fullname', 'users.avatar as user_avatar', 'users.email as user_email', 'drivers.first_name as driver_firstname', 'drivers.surname as driver_surname', 'drivers.avatar as driver_avatar', 'drivers.email as driver_email')
            ->join('users', 'delivery_histories.user_id', '=', 'users.id')
            ->join('riders', 'delivery_histories.riders_id', '=', 'drivers.id')
            ->where('user_id', $id)
            ->get();
        return $this->sendResponse($deliverys, 'User deliveries');
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




    // public function deliveryHistory(Request $request)
    // {
    //     $user = Auth::guard('api')->user();
    //     $history = DB::table('delivery_histories')->where('users_id', $user->id)->get();
    //     return $this->sendResponse($history, 'Success');
    // }
}
