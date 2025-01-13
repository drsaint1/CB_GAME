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
// use App\Notifications\TripRequestNotification;

class TripController extends BaseController
{
    private $booking, $notice;
    private $kobo;

    public function __construct(BookingService $booking, KoboService $kobo)
    {
        $this->booking = $booking;
        $this->kobo = $kobo;
        $this->notice = new PushNotification();
    }


    public function get_driver_in_range($latitude, $longitude, $radius = 100000)
    {
        $drivers = Driver::select(
            "*",
            "driver_vehicles.id as driver_vehicle_id",
            DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                        * cos(radians(c_lat))
                        * cos(radians(c_long) - radians(" . $longitude . "))
                        + sin(radians(" . $latitude . "))
                        * sin(radians(c_lat))) AS distance")
        )
            ->having("distance", "<", 100000)
            ->join('driver_vehicles', 'drivers.id', '=', 'driver_vehicles.driver_id')
            ->where('drivers.availability', 'available') // Filter by availability status
            ->limit(20)
            ->get();

        return $drivers;
    }

    // public function bookRide(Request $request)
    // {
    //     $fromAddress = $request->from;
    //     $toAddress = $request->to;

    //     $price_per_km_standard = 300;
    //     $price_per_km_luxury = 400; // Set a higher price for luxury cars

    //     $getData = $this->booking->getDistance($fromAddress, $toAddress);
    //     // $coordinate = $this->booking->getCoordinate($fromAddress);

    //     if ($getData) {

    //         $km = $getData['rows'][0]['elements'][0]['distance']['text'];
    //         $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
    //         $from = $getData['origin_addresses'][0];
    //         $to = $getData['destination_addresses'][0];
    //         $rm_km = substr($km, 0, -3);
    //         $total_price_standard = $price_per_km_standard * $rm_km;
    //         $total_price_luxury = $price_per_km_luxury * $rm_km;



    //         $price = [
    //             'standard_price' =>  $total_price_standard,
    //             'luxury_price' =>  $total_price_luxury
    //         ];

    //         $newData = [
    //             'distance' => $km,
    //             'duration' => $duration,
    //             'from_address' => $from,
    //             'to_address' => $to,
    //             'price' => $price
    //         ];
    //         return $this->sendResponse($newData, 'trip or ride details');
    //     } else {
    //         return $this->sendError('Error', 'Failed to retrieve distance for the provided address.');
    //     }
    // }

    public function bookRide(Request $request)
    {
        $fromAddress = $request->from;
        $toAddress = $request->to;

        $price_per_km_standard = 300;
        $price_per_km_luxury = 400; // Set a higher price for luxury cars

        $getData = $this->booking->getDistance($fromAddress, $toAddress);

        if ($getData && isset($getData['rows'][0]['elements'][0]['distance']['text']) && isset($getData['rows'][0]['elements'][0]['duration']['text'])) {

            $km = $getData['rows'][0]['elements'][0]['distance']['text'];
            $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
            $from = $getData['origin_addresses'][0];
            $to = $getData['destination_addresses'][0];
            $rm_km = substr($km, 0, -3);
            $total_price_standard = $price_per_km_standard * $rm_km;
            $total_price_luxury = $price_per_km_luxury * $rm_km;

            $price = [
                'standard_price' =>  $total_price_standard,
                'luxury_price' =>  $total_price_luxury
            ];

            $newData = [
                'distance' => $km,
                'duration' => $duration,
                'from_address' => $from,
                'to_address' => $to,
                'price' => $price
            ];
            return $this->sendResponse($newData, 'trip or ride details');
        } else {
            return $this->sendError('Error', 'Failed to retrieve distance for the provided address.');
        }
    }

    // public function bookRide(Request $request)
    // {
    //     $fromAddress = $request->from;
    //     $toAddress = $request->to;

    //     $price_per_km_standard = 300;
    //     $price_per_km_luxury = 400;

    //     $getData = $this->booking->getDistance($fromAddress, $toAddress);

    //     if ($getData) {
    //         $km = $getData['rows'][0]['elements'][0]['distance']['text'];
    //         $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
    //         $from = $getData['origin_addresses'][0];
    //         $to = $getData['destination_addresses'][0];
    //         $rm_km = (float) filter_var($km, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    //         $total_price_standard = $price_per_km_standard * $rm_km;
    //         $total_price_luxury = $price_per_km_luxury * $rm_km;

    //         $price = [
    //             'standard_price' =>  $total_price_standard,
    //             'luxury_price' =>  $total_price_luxury
    //         ];

    //         $newData = [
    //             'distance' => $km,
    //             'duration' => $duration,
    //             'from_address' => $from,
    //             'to_address' => $to,
    //             'price' => $price
    //         ];
    //         return $this->sendResponse($newData, 'trip or ride details');
    //     } else {
    //         return $this->sendError('Error', 'Failed to retrieve distance for the provided address.');
    //     }
    // }




    //avialable drivers in range.
    public function availableDrivers(Request $request)
    {
        $fromAddress = $request->from;
        $coordinate = $this->booking->getCoordinate($fromAddress);

        if ($coordinate) {
            $latitude = $coordinate['latitude'];
            $longitude = $coordinate['longitude'];

            $standard_drivers = [];
            $luxury_drivers = [];

            $driversInRange = $this->get_driver_in_range(number_format($longitude, 6), number_format($latitude, 6));

            foreach ($driversInRange as $driver) {
                if (isset($driver['car_type'])) {
                    if ($driver['car_type'] == 'standard') {
                        $standard_drivers[] = $driver;
                    } elseif ($driver['car_type'] == 'luxury') {
                        $luxury_drivers[] = $driver;
                    }
                }
            }
            $drivers = [
                'standard_drivers' => $standard_drivers,
                'luxury_drivers' => $luxury_drivers,
            ];

            return $this->sendResponse($drivers, 'Available Drivers');
        } else {
            return $this->sendError('Error', 'Failed to retrieve coordinates for the provided address.');
        }
    }


    //create a trip or order a ride
    public function createTrip(Request $request)
    {
        $from_lat = $this->booking->getCoordinate($request->from_address);
        $to_lat =  $this->booking->getCoordinate($request->to_address);
        $from_long = $this->booking->getCoordinate($request->from_address);
        $to_long = $this->booking->getCoordinate($request->to_address);
        // $from_lat = ['latitude' => 40.712776, 'longitude' => -74.005974]; // Example coordinates for New York City
        // $to_lat = ['latitude' => 34.052235, 'longitude' => -118.243683]; // Example coordinates for Los Angeles
        // $from_long = ['latitude' => 40.712776, 'longitude' => -74.005974];
        // $to_long = ['latitude' => 34.052235, 'longitude' => -118.243683];

        $auth_user = Auth::guard('api')->user();
        $user_id = Auth::guard('api')->user()->id;
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $pay_type = $request->payment_type;

        $car_type = $request->car_type;
        if (!in_array($car_type, ['standard', 'luxury'])) {
            return $this->sendError('Error', 'Invalid car type selected.');
        }


        // Find drivers in range with the selected car type
        $driversInRange = $this->get_driver_in_range(number_format($from_lat['latitude'], 6), number_format($from_long['longitude'], 6));
        $selectedDrivers = [];
        foreach ($driversInRange as $driver) {
            if (isset($driver['car_type']) && $driver['car_type'] == $car_type && $driver['availability'] == 'available') {
                $selectedDrivers[] = $driver;
            }
        }

        if ($pay_type == 'wallet') {
            $user_wallet = UserWallet::where('user_id', $user_id)->first();

            if ((int)$user_wallet->balance < (int)$request->amount) {
                return $this->sendError('warning', 'oOps, You have insufficient wallet balance to order a ride');
            } else {
                $trip = new TripHistory();
                $trip->user_id = $user_id;
                $trip->from_address = $request->from_address;
                $trip->to_address = $request->to_address;
                $trip->from_lat = number_format($from_lat['latitude'], 6);
                $trip->to_lat = number_format($to_lat['latitude'], 6);
                $trip->from_long = number_format($from_long['longitude'], 6);
                $trip->to_long = number_format($to_long['latitude'], 6);
                $trip->t_fare = $request->amount;
                $trip->payment_type = $request->payment_type;
                $trip->car_type = $request->car_type;
                $trip->payment_status = 'pending';
                $sv = $trip->save();

                if ($sv) {
                    $details = [
                        "user" => $auth_user,
                        "trip" => $trip,
                    ];


                    $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';
                    $this->notice->sendPushNotification("01", 'Order Trip', $userMessage, [$user->token], null, $details);

                    event(new TripRequest($details, $selectedDrivers, $user));
                    // Send push notifications to selected drivers
                    foreach ($selectedDrivers as $driver) {
                        $driverUser = Driver::find($driver['id']); // Assuming driver['id'] is the ID of the driver
                        if ($driverUser) {
                            $driverMessage = 'Hello ' . $driverUser->username . ', you have a trip request. Please accept the trip.';
                            $this->notice->sendPushNotification("01", 'Trip Request', $driverMessage, [$driverUser->token], null, $details);
                        }
                    }
                    // event(new TripRequest($trip, $selectedDrivers));

                    // if (isset($auth_user->email) && $auth_user->emailVerified == 1) {
                    //     $msg = " you have booked a trip successfully, a driver will get back to you soon . Thanks!!!";
                    //     $subject = "Trip Notification";
                    //     $this->kobo->anyEmailNotification($auth_user->email, $auth_user->name, $msg, $subject);
                    // }
                    return $this->sendResponse($selectedDrivers, 'order a ride Successful , searching for drivers');
                } else {
                    return $this->sendError('Error', 'oOps, Something went wrong!!');
                }
            }
        } else {
            $trip = new TripHistory();
            $trip->user_id = $user_id;
            $trip->from_address = $request->from_address;
            $trip->to_address = $request->to_address;
            $trip->from_lat = number_format($from_lat['latitude'], 6);
            $trip->to_lat = number_format($to_lat['latitude'], 6);
            $trip->from_long = number_format($from_long['longitude'], 6);
            $trip->to_long = number_format($to_long['latitude'], 6);
            $trip->t_fare = $request->amount;
            $trip->car_type = $request->car_type;
            $trip->payment_type = $request->payment_type;
            $trip->payment_status = 'pending';
            $sv = $trip->save();

            if ($sv) {
                $details = [
                    "user" => $auth_user,
                    "trip" => $trip,
                ];
                // Send push notifications
                $userMessage = 'Hello ' . $user->username . ', you have booked a trip successfully. A driver will get back to you soon. Thanks';
                $this->notice->sendPushNotification("01", 'Order Trip', $userMessage, [$user->token], null, $trip);

                // Send push notifications to selected drivers
                foreach ($selectedDrivers as $driver) {
                    $driverUser = Driver::find($driver['id']); // Assuming driver['id'] is the ID of the driver
                    if ($driverUser) {
                        $driverMessage = 'Hello ' . $driverUser->username . ', you have a trip request. Please accept the trip.';
                        $this->notice->sendPushNotification("01", 'Trip Request', $driverMessage, [$driverUser->token], null, $details);
                    }
                }

                event(new TripRequest($trip, $selectedDrivers, $user));
                // if (isset($auth_user->email) && $auth_user->emailVerified == 1) {
                //     $msg = " you have booked a trip successfully, a driver will get back to you soon . Thanks!!!";
                //     $subject = "Trip Notification";
                //     $this->kobo->anyEmailNotification($auth_user->email, $auth_user->name, $msg, $subject);
                // }

                return $this->sendResponse($selectedDrivers, 'Trip Booked Successfully!!');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
        //dispatch to the drivers,don't input the drivers id
    }

    public function showTripDetails()
    {
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


    public function driverAcceptTrip(Request $request, $tripId)
    {
        // $id = $request->driver_id;
        $id = Auth::guard('driver_api')->user()->id;
        $driver = Driver::where('id', $id)->first();
        $driverVehicle = DriverVehicle::where('driver_id', $id)->first();
        $trip = TripHistory::where('id', $tripId)->first();
        $completed = TripHistory::where('driver_id', $id)->where('trip_status', 'completed')->count();
        $rating = $driver->ratings()->avg('review_count');
        $user = User::where('id', $trip->user_id)->first();
        if ($trip->trip_status == 'accept') {
            return $this->sendError('Error', 'Trip has been accepted by another driver/not available/does\'nt exist');
        } else {
            // Update the existing trip instance with driver_id and driver_location
            $trip->trip_status = 'accept';
            $trip->driver_id = $id;
            $trip->driver_location = $request->driver_location;
            $sv = $trip->save();

            $this->driverInitiateTripChat($trip->id);

            if ($sv) {
                $data = [
                    'trip' => $trip,
                    'driver_profile' => $driver,
                    'driver_vehicle' => $driverVehicle,
                    'driver_trip_rating' => $rating,
                    'driver_total_trip' => $completed,
                ];
                // Dispatch the event after updating the trip
                event(new TripAccepted($data, $user));

                $userMessage = 'Hi ' . $user->username . ', your trip order request have been accepted and you driver is on the way, please wait for him.';
                $this->notice->sendPushNotification("01", 'Trip Accepted', $userMessage, [$user->token], null, $trip);
                $this->notice->sendPushNotification("01", 'Trip Accepted', 'Hi, you just accepted a trip order', array($driver->token), null, $trip);

                return $this->sendResponse($data, 'Trip Booked Successfully!!');
            }
        }
    }

    public function driverHasArrived(Request $request, $tripId)
    {
        // $id = $request->driver_id;
        $id = Auth::guard('driver_api')->user()->id;
        $driver = Driver::where('id', $id)->first();
        $trip = TripHistory::where('id', $tripId)->where('driver_id', $id)->first();
        $user = User::where('id', $trip->user_id)->first();

        $details = [
            "driver" => $driver,
            "trip" => $trip
        ];

        event(new DriverArrived($details, $user));

        $userMessage = 'Hi ' . $user->username . ', your driver has arrived, your trip will start soon.';
        $this->notice->sendPushNotification("01", 'Driver has Arrived', $userMessage, [$user->token], null, $trip);
        $this->notice->sendPushNotification("01", 'User Notified', 'take time to find your user', array($driver->token), null, $trip);

        return $this->sendResponse($details, 'user notified,driver has arrived');
    }



    public function tripStarted($tripId)
    {
        $trip = TripHistory::find($tripId);
        if ($trip) {
            //  $auth_user = Auth::guard('api')->user();
            $driver_id = Auth::guard('driver_api')->user()->id;
            // $user_id = Auth::guard('api')->user()->id;
            // $driver = Driver::where('id', $driver_id)->first();
            $user = User::where('id', $trip->user_id)->first();
            $pay_type = $trip->payment_type;

            if ($pay_type == 'wallet') {
                $user_wallet = UserWallet::where('user_id', $trip->user_id)->first();
                $driver_wallet = DriverWallet::where('driver_id', $trip->driver_id)->first();
                $driver_percent = 80;
                $driver_share = ($driver_percent / 100) * $trip->t_fare;

                if ((int)$user_wallet->balance < (int)$trip->t_fare) {
                    return $this->sendError('warning', 'oOps, You have insufficient wallet balance to order a ride');
                } else {
                    $driver = Driver::where('id', $trip->driver_id)->first();
                    // $this->notifyDriver($driver, $trip, $user);
                    $user_wallet->balance = $user_wallet->balance - $trip->t_fare;
                    $user_wallet->update();
                    $driver_wallet->balance = $driver_wallet->balance + $driver_share;
                    $driver_wallet->update();

                    $trip->payment_status = 'pending';
                    $trip->is_started = true;
                    $sv = $trip->save();

                    if ($sv) {
                        $details = [
                            "driver" => $driver,
                            "trip" => $trip,
                            "user" => $user
                        ];
                        $userMessage = 'Hello ' . $user->username . ', your trip has started to drive you will be taken to you destination';
                        $this->notice->sendPushNotification("01", 'Trip Started', $userMessage, [$user->token], null, $trip);
                        // Dispatch the event after updating the trip
                        event(new TripStarted($details, $user));
                        // Delete the chat associated with the completed trip
                        $this->deleteChat($tripId);
                        return $this->sendResponse($details, 'Trip has started');
                    }
                }
            }
        } else {
            return $this->sendError('error', 'Trip not found');
        }
        //dispatch to the users and update that trip has started
    }


    public function tripComplete($tripId)
    {
        $trip = TripHistory::find($tripId);
        if ($trip) {

            $driverid = Auth::guard('driver_api')->user()->id;
            $driver = Driver::where('id', $driverid)->first();
            $user = User::where('id', $trip->user_id)->first();
            $trip->payment_status = 'paid';
            $trip->trip_status = 'completed';
            $trip->is_complete = true;
            $sv = $trip->save();


            if ($sv) {
                $details = [
                    "driver" => $driver,
                    "trip" => $trip,
                    "user" => $user
                ];
                // Dispatch the event after updating the trip
                event(new TripEnded($details, $user));

                $userMessage = 'Hello ' . $user->username . ', your trip is now completed';
                $this->notice->sendPushNotification("01", 'Trip Started', $userMessage, [$user->token], null, $trip);

                if (isset($user->email) && $user->emailVerified == 1) {
                    $msg = "your ride was complete, give the driver a rating!!";
                    $subject = "Kobosquare Trip Completed";
                    $this->kobo->anyEmailNotification($user->email, $user->name, $msg, $subject);
                }
                // Delete the chat associated with the completed trip
                $this->deleteChat($tripId);
                return $this->sendResponse($details, 'Ride is completed');
            }
        } else {
            return $this->sendError('error', 'Trip not found');
        }
    }


    public function driversCurrentlocation()
    {
        //dispatch to the users and constantly update the drivers locaion trip has started
    }

    public function updateDriverLocation(Request $request, $driverId)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $driver = Driver::find($driverId);
        if (!$driver) {
            return response()->json(['error' => 'Driver not found'], 404);
        }

        $driver->c_lat = $request->latitude;
        $driver->c_long = $request->longitude;
        $driver->save();

        event(new DriverLocationUpdated($driverId, $request->latitude, $request->longitude));

        return response()->json(['message' => 'Driver location updated successfully'], 200);
    }


    public function cancelTrip(Request $request)
    {
        $id = Auth::guard('driver_api')->user()->id;
        $trip = TripHistory::where('id', $request->trip_id)->where('driver_id', $id)->first();
        if ($trip->trip_status == 'pending' || $trip->trip_status == 'completed' || $trip->trip_status == 'cancelled' || $trip->trip_status == 'decline') {
            return $this->sendError('Error', 'Trip cannot be cancelled');
        } else {
            $trip->trip_status = 'cancelled';
            $tu = $trip->update();
            if ($tu) {
                event(new TripCanceled($trip, $trip->user_id));
                return $this->sendResponse($trip, 'Trip Cancelled');
            } else {
                return $this->sendError('Error', 'oOps, Something went wrong!!');
            }
        }
    }




    public function driverInitiateTripChat($trip_id)
    {
        $init = new BookingChat();
        $init->trip_history_id = $trip_id;
        $sv = $init->save();
        return $sv;
    }



    public function driverReplyChat(Request $request, $userid)
    {
        $driver = Auth::guard('driver_api')->user();
        if (!$driver) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = BookingChat::where('trip_history_id', $request->trip_id)->where('user_id', $userid)->first();
        if (!$chat) {
            // Create a new chat if it doesn't exist
            $chat = new BookingChat();
            $chat->trip_history_id = $request->trip_id;
            $chat->user_id = $driver->id;
            $chat->driver_id = $userid;
            $chat->message = $request->text;
            $chat->save();
            return $this->sendResponse($chat, 'Chat initiated successfully');
        } else {
            $new = new BookingChatMessage();
            $new->booking_chat_id = $chat->id;
            $new->user_entity_id = $driver->id;
            $new->text = $request->text;
            $sv = $new->save();

            if ($sv) {
                event(new BookingChatMessageCreated($new));

                $user = User::where('id', $userid)->first();
                $this->notice->sendPushNotification("01", 'Trip Message', $request->text, array($user->token), null, $new);

                return $this->sendResponse($new, 'Message Sent');
            } else {
                return $this->sendError('error', 'Oops, Something went wrong!');
            }
        }
    }



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
