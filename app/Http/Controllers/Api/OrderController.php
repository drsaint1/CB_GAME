<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\OrderChatMessageCreated;
use App\Models\KoboOrder;
use App\Models\KoboOrderItem;
use App\Models\KoboServiceOrder;
use App\Models\KoboServiceOrderItem;
use App\Models\KoboRequestOrder;
use App\Models\KoboRequestOrderItem;
use App\Models\KoboMerchant;
use App\Models\MerchantProduct;
use App\Models\MerchantProductImage;
use App\Models\MerchantRating;
use App\Models\MerchantService;
use App\Models\MerchantServiceImage;
use App\Models\MerchantShortStay;
use App\Models\MerchantShortStayImage;
use App\Models\Order;
use App\Models\orderboOrder;
use App\Models\UserWallet;
use App\Models\User;
use App\Models\OrderChat;
use App\Models\OrderChatMessage;
use App\Services\RingoService;
use App\Services\KoboService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\KoboNotification;
use Illuminate\Support\Facades\DB;
use App\Services\PushNotification;
use App\Models\TableEntity;

class OrderController extends BaseController
{
    private $ringo;
    private $kobo;
    private $table;
    private $notice;
    private $email;

    public function __construct(RingoService $ringo, KoboService $kobo, EmailService $email)
    {
        $this->ringo = $ringo;
        $this->kobo = $kobo;
        $this->email = $email;
        $this->table = new TableEntity();
        $this->notice = new PushNotification();
    }

    public function initiateOrder(Request $request)
    {
        $inputs = $request->input();
        $client = Auth::guard('api')->user();
        $accountBal = $this->ringo->getWalletBalance($client->id);
        if (isset($client->bvn)) {
            if ($request->pin && Hash::check($request->pin, $client->pin)) {
                if ((int)$accountBal->balance >= (int)$inputs['sub_total']) {
                    $order = new KoboOrder();
                    $orderno= 'KBO-ORD-' . strtoupper(uniqid());
                    $order->order_number = $orderno;
                    $order->user_id = $client->id;
                    $order->merchant_id = $inputs['merchant_uuid'];
                    $order->grand_total = $inputs['sub_total'];
                    // $order->order_item = $inputs['total_quantity'];
                    $order->order_type = $inputs['order_type'];
                    $order->payment_method = $inputs['payment_method'];
                    $order->delivery_type = $inputs['delivery_type'];
                    $order->schedule_delivery = $inputs['schedule_delivery'];
                    $order->shedule_date = $inputs['shedule_date'];
                    $order->shedule_time = $inputs['shedule_time'];
                    $order->schedule_stay = $inputs['schedule_stay'];
                    $order->move_in_date = $inputs['move_in_date'];
                    $order->move_in_time = $inputs['move_in_time'];
                    $order->move_out_date = $inputs['move_out_date'];
                    $order->move_out_time = $inputs['move_out_time'];
                    $order->delivery_address = $inputs['delivery_address'];
                    $order->phone_number = $inputs['phone_number'];
                    $order->notes = $inputs['notes'];
                    $order->save();

                    $merchant = KoboMerchant::where('uuid', $inputs['merchant_uuid'])->first();

                    if ($order) {
                        $decode = json_encode($request->products);
                        foreach ($request->products as $item) {
                            $orderItem = new KoboOrderItem([
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity'],
                                'price' => $item['price']
                            ]);
                            $order->items()->save($orderItem);
                            // $order->update(['products' => json_encode($request->products)]);
                        }

                        if ($inputs['payment_method'] == "wallet") {
                            $user_wallet = UserWallet::where('user_id', $client->id)->first();
                            $user_wallet->balance = $user_wallet->balance - $inputs['sub_total'];
                            $ws = $user_wallet->update();
                            if ($ws) {
                                $ko = KoboOrder::where('id', $order->id)->first();
                                $ko->payment_status = 'paid';
                                $ko->update();

                                $authmsg = "You just placed an order";
                                Notification::send($client, new KoboNotification(['title' => 'New Order', 'message' => $authmsg, "order" => $order]));

                                $userMessage = 'Hello ' . $client->username . ', You just placed an order';
                                $this->notice->sendPushNotification("01", 'Order Placed', $userMessage, [$client->token], null, $order);

                                $trans_ref = $this->kobo->generateTransRef();

                                if (isset($client->email) && $client->emailVerified == 1) {
                                    $msg = "Your order request has been submitted successfully,We let you know when your items will be displaced!!!";
                                    $subject = "Order request";
                                    $type = "Order Payment";
                                    $method ="Wallet";
                                    $paymentStatus= "Successful";
                                    $total =  $order->grand_total;
                                    $merchantName=$merchant->business_name;
                                    // $this->kobo->anyEmailNotification($client->email, $client->name, $msg, $subject);
                                    $this->email->orderEmailNotification($client->email, $client->name, $msg, $subject,$trans_ref,$orderno,$type,$method,$merchantName,$total,$paymentStatus);
                                }



                                $dtat = [
                                    'user_id' => $client->id,
                                    'trans_ref' => $trans_ref,
                                    'type' => 'Kobo Order Payment',
                                    'method' => "Wallet",
                                    "status" => "Successful",
                                    'amount' => $order->grand_total,
                                    'from' => $client->id,
                                    'to' => "Kobosquare"
                                ];
                                $this->table->insertNewEntry('transactions', 'id', $dtat);

                                return $this->sendResponse($order, "Your order is submitted successfully!!!");
                            }
                        }
                    } else {
                        return $this->sendError('error', 'oOps, Something wend wrong!!!');
                    }
                } else {
                    return $this->sendError('error', 'oOps, You have Insufficient Fund in your wallet!!!');
                }
            } else {
                return $this->sendError('Transaction failed', 'Your pin is incorrect');
            }
        } else {
            return $this->sendError('error', 'oOps, Please verify your bvn to continue enjoying our services!!!');
        }
    }


    public function shortStayOrders(Request $request)
    {
        $inputs = $request->input();
        $client = Auth::guard('api')->user();
        $accountBal = $this->ringo->getWalletBalance($client->id);
        if (isset($client->bvn)) {
            if ($request->pin && Hash::check($request->pin, $client->pin)) {
                if ((int)$accountBal->balance >= (int)$inputs['sub_total']) {
                    $order = new KoboRequestOrder();
                    $orderno= 'KBO-ORD-' . strtoupper(uniqid());
                    $order->order_number = $orderno;
                    $order->user_id = $client->id;
                    $order->merchant_id = $inputs['merchant_uuid'];
                    $order->grand_total = $inputs['sub_total'];
                    // $order->order_item = $inputs['total_quantity'];
                    $order->order_type = "request";
                    $order->payment_method = $inputs['payment_method'];
                    // $order->schedule_delivery = $inputs['schedule_delivery'];
                    $order->shedule_date = $inputs['shedule_date'];
                    $order->shedule_time = $inputs['shedule_time'];
                    $order->schedule_stay = $inputs['schedule_stay'];
                    $order->move_in_date = $inputs['move_in_date'];
                    $order->move_in_time = $inputs['move_in_time'];
                    $order->move_out_date = $inputs['move_out_date'];
                    $order->move_out_time = $inputs['move_out_time'];
                    $order->phone_number = $inputs['phone_number'];
                    $order->notes = $inputs['notes'];
                    $order->save();

                    $merchant = KoboMerchant::where('uuid', $inputs['merchant_uuid'])->first();

                    if ($order) {
                        $decode = json_encode($request->requests);
                        foreach ($request->requests as $item) {
                            $orderItem = new KoboRequestOrderItem([
                                'product_request_id' => $item['product_request_id'],
                                'quantity' => $item['quantity'],
                                'price' => $item['price']
                            ]);
                            $order->items()->save($orderItem);
                            // $order->update(['products' => json_encode($request->products)]);
                        }
                        if ($inputs['payment_method'] == "wallet") {

                            $user_wallet = UserWallet::where('user_id', $client->id)->first();
                            $user_wallet->balance = $user_wallet->balance - $inputs['sub_total'];
                            $ws = $user_wallet->update();
                            if ($ws) {
                                $ko = KoboRequestOrder::where('id', $order->id)->first();
                                $ko->payment_status = 'paid';
                                $ko->update();

                                $authmsg = "You just made a request";
                                Notification::send($client, new KoboNotification(['title' => 'New Order', 'message' => $authmsg, "order" => $order]));

                                $userMessage = 'Hello ' . $client->username . ', You made a request';
                                $this->notice->sendPushNotification("01", 'Request  Placed', $userMessage, [$client->token], null, $order);

                                $trans_ref = $this->kobo->generateTransRef();

                                if (isset($client->email) && $client->emailVerified == 1) {
                                    $msg = "Your order request has been submitted successfully,We let you know when your items will be displaced!!!";
                                    $subject = "Short Stay Request Notification";
                                    $type = "Short Stay Payment";
                                    $method ="Wallet";
                                    $paymentStatus= "Successful";
                                    $total =  $order->grand_total;
                                    $merchantName=$merchant->business_name;
                                    // $this->kobo->anyEmailNotification($client->email, $client->name, $msg, $subject);
                                    $this->email->orderEmailNotification($client->email, $client->name, $msg, $subject,$trans_ref,$orderno,$type,$method,$merchantName,$total,$paymentStatus);
                                }

                                $dtat = [
                                    'user_id' => $client->id,
                                    'trans_ref' => $trans_ref,
                                    'type' => 'Order',
                                    'method' => "Wallet",
                                    "status" => "Successful",
                                    'amount' => $order->grand_total,
                                    'from' => $client->id,
                                    'to' => "Kobosquare"
                                ];
                                $this->table->insertNewEntry('transactions', 'id', $dtat);


                                return $this->sendResponse($order, "Your order is submitted successfully!!!");
                            }
                        }
                    } else {
                        return $this->sendError('error', 'oOps, Something wend wrong!!!');
                    }
                } else {
                    return $this->sendError('error', 'oOps, You have Insufficient Fund in your wallet!!!');
                }
            } else {
                return $this->sendError('Transaction failed', 'Your pin is incorrect');
            }
        } else {
            return $this->sendError('error', 'oOps, Please verify your bvn to continue enjoying our services!!!');
        }
    }


    public function koboFuelRequest(Request $request)
    {
        $inputs = $request->input();
        $client = Auth::guard('api')->user();
        $accountBal = $this->ringo->getWalletBalance($client->id);
        if (isset($client->bvn)) {
            if ($request->pin && Hash::check($request->pin, $client->pin)) {
                if ((int)$accountBal->balance >= (int)$inputs['sub_total']) {
                    $order = new KoboRequestOrder();
                    $orderno= 'KBO-ORD-' . strtoupper(uniqid());
                    $order->order_number = $orderno;
                    $order->user_id = $client->id;
                    $order->merchant_id = $inputs['merchant_uuid'];
                    $order->grand_total = $inputs['sub_total'];
                    $order->order_type = "requests";
                    $order->fuel_quantity = $inputs['fuel_quantity'];
                    $order->fuel_type = $inputs['fuel_type'];
                    $order->payment_method = $inputs['payment_method'];
                    $order->delivery_type = $inputs['delivery_type'];
                    $order->schedule_delivery = $inputs['schedule_delivery'];
                    $order->delivery_address = $inputs['delivery_address'];
                    $order->phone_number = $inputs['phone_number'];
                    $order->notes = $inputs['notes'];
                    $order->save();

                    $merchant = KoboMerchant::where('uuid', $inputs['merchant_uuid'])->first();

                    if ($order) {
                        $decode = json_encode($request->requests);
                        foreach ($request->requests as $item) {
                            $orderItem = new KoboRequestOrderItem([
                                'product_request_id' => $item['product_service_id'],
                                'quantity' => $item['quantity'],
                                'price' => $item['price']
                            ]);
                            $order->items()->save($orderItem);
                            // $order->update(['products' => json_encode($request->products)]);
                        }

                        if ($inputs['payment_method'] == "wallet") {
                            $user_wallet = UserWallet::where('user_id', $client->id)->first();
                            $user_wallet->balance = $user_wallet->balance - $inputs['sub_total'];
                            $ws = $user_wallet->update();
                            if ($ws) {
                                $ko = KoboRequestOrder::where('id', $order->id)->first();
                                $ko->payment_status = 'paid';
                                $ko->update();

                                $authmsg = "You just placed an order for fuel";
                                Notification::send($client, new KoboNotification(['title' => 'New Order', 'message' => $authmsg, "order" => $order]));

                                $userMessage = 'Hello ' . $client->username . ', You just placed an order for fuel';
                                $this->notice->sendPushNotification("01", 'Fuel Request Placed', $userMessage, [$client->token], null, $order);

                                $trans_ref = $this->kobo->generateTransRef();

                                if (isset($client->email) && $client->emailVerified == 1) {
                                    $msg = "Your fuel request has been submitted successfully,We let you know when your items will be displaced!!!";
                                    $subject = "Kobo Fuel Request  Notification";
                                    $type = "Kobo Fuel Payment";
                                    $method ="Wallet";
                                    $paymentStatus= "Successful";
                                    $total =  $order->grand_total;
                                    $merchantName=$merchant->business_name;
                                    $this->email->orderEmailNotification($client->email, $client->name, $msg, $subject,$trans_ref,$orderno,$type,$method,$merchantName,$total,$paymentStatus);
                                }



                                $dtat = [
                                    'user_id' => $client->id,
                                    'trans_ref' => $trans_ref,
                                    'type' => 'Kobo Fuel Payment',
                                    'method' => "Wallet",
                                    "status" => "Successful",
                                    'amount' => $order->grand_total,
                                    'from' => $client->id,
                                    'to' => "Kobosquare"
                                ];
                                $this->table->insertNewEntry('transactions', 'id', $dtat);

                                return $this->sendResponse($order, "Your fuel request order is successful");
                            }
                        }
                    } else {
                        return $this->sendError('error', 'oOps, Something wend wrong!!!');
                    }
                } else {
                    return $this->sendError('error', 'oOps, You have Insufficient Fund in your wallet!!!');
                }
            } else {
                return $this->sendError('Transaction failed', 'Your pin is incorrect');
            }
        } else {
            return $this->sendError('error', 'oOps, Please verify your bvn to continue enjoying our services!!!');
        }
    }



    public function OrderService(Request $request)
    {
        $inputs = $request->input();
        $client = Auth::guard('api')->user();
        $accountBal = $this->ringo->getWalletBalance($client->id);
        if (isset($client->bvn)) {
            if ($request->pin && Hash::check($request->pin, $client->pin)) {
                if ((int)$accountBal->balance >= (int)$inputs['sub_total']) {
                    $order = new KoboServiceOrder();
                    $orderno= 'KBO-ORD-' . strtoupper(uniqid());
                    $order->order_number = $orderno;
                    $order->user_id = $client->id;
                    $order->merchant_id = $inputs['merchant_uuid'];
                    $order->grand_total = $inputs['sub_total'];
                    // $order->order_item = $inputs['total_quantity'];
                    // $order->order_type = $inputs['order_type'];
                    $order->order_type = "service";
                    $order->payment_method = $inputs['payment_method'];
                    $order->schedule_delivery = $inputs['schedule_delivery'];
                    $order->shedule_date = $inputs['shedule_date'];
                    $order->shedule_time = $inputs['shedule_time'];
                    $order->other_delivery_details = $inputs['other_delivery_details'];
                    $order->delivery_address = $inputs['delivery_address'];
                    $order->delivery_type = $inputs['delivery_type'];
                    $order->phone_number = $inputs['phone_number'];
                    $order->notes = $inputs['notes'];
                    $order->save();

                    if ($order) {
                        $decode = json_encode($request->services);
                        foreach ($request->services as $item) {
                            $orderItem = new KoboServiceOrderItem([
                                'product_service_id' => $item['product_service_id'],
                                'quantity' => $item['quantity'],
                                'price' => $item['price']
                            ]);
                            $order->items()->save($orderItem);
                            // $order->update(['products' => json_encode($request->products)]);
                        }


                        $merchant = KoboMerchant::where('uuid', $inputs['merchant_uuid'])->first();

                        if ($inputs['payment_method'] == "wallet") {
                            $user_wallet = UserWallet::where('user_id', $client->id)->first();
                            $user_wallet->balance = $user_wallet->balance - $inputs['sub_total'];
                            $ws = $user_wallet->update();
                            if ($ws) {
                                $ko = KoboServiceOrder::where('id', $order->id)->first();
                                $ko->payment_status = 'paid';
                                $ko->update();


                                $authmsg = "You just ordered for a service";
                                Notification::send($client, new KoboNotification(['title' => 'You ordered a Service', 'message' => $authmsg, "order" => $order]));

                                $userMessage = 'Hello ' . $client->username . ', You just ordered for a service';
                                $this->notice->sendPushNotification("01", 'Service order placed', $userMessage, [$client->token], null, $order);

                                $trans_ref = $this->kobo->generateTransRef();

                                if (isset($client->email) && $client->emailVerified == 1) {
                                    $msg = "you ordered for a service,We will let you know when the service will be available!!!";
                                    $subject = "Ordered Service Notification";
                                    $type = "Kobo Service Payment";
                                    $method ="Wallet";
                                    $paymentStatus= "Successful";
                                    $total =  $order->grand_total;
                                    $merchantName=$merchant->business_name;
                                    $this->email->orderEmailNotification($client->email, $client->name, $msg, $subject,$trans_ref,$orderno,$type,$method,$merchantName,$total,$paymentStatus);
                                }


                                $dtat = [
                                    'user_id' => $client->id,
                                    'trans_ref' => $trans_ref,
                                    'type' => 'Service Payment',
                                    'method' => "Wallet",
                                    "status" => "Successful",
                                    'amount' => $order->grand_total,
                                    'from' => $client->id,
                                    'to' => "Kobosquare"
                                ];
                                $this->table->insertNewEntry('transactions', 'id', $dtat);

                                return $this->sendResponse($order, "Your order for a service is submitted successfully!!!");
                            }
                        }
                    } else {
                        return $this->sendError('error', 'oOps, Something wend wrong!!!');
                    }
                } else {
                    return $this->sendError('error', 'oOps, You have Insufficient Fund in your wallet!!!');
                }
            } else {
                return $this->sendError('Transaction failed', 'Your pin is incorrect');
            }
        } else {
            return $this->sendError('error', 'oOps, Please verify your bvn to continue enjoying our services!!!');
        }
    }



    public function getUserOrders($userId)
    {
        // Query to join kobo_orders, kobo_merchants, kobo_order_items, merchant_products, and merchant_product_images
        $orders = DB::table('kobo_orders')
            ->join('kobo_merchants', 'kobo_orders.merchant_id', '=', 'kobo_merchants.uuid')
            ->join('kobo_order_items', 'kobo_orders.id', '=', 'kobo_order_items.kobo_order_id')
            ->join('merchant_products', 'kobo_order_items.product_id', '=', 'merchant_products.uuid')
            ->join('kobo_merchants as product_merchants', 'merchant_products.kobo_merchants_id', '=', 'product_merchants.uuid')
            ->leftJoin('merchant_product_images', 'merchant_products.uuid', '=', 'merchant_product_images.merchant_products_id')
            ->where('kobo_orders.user_id', $userId)
            ->select(
                'kobo_orders.*',
                'kobo_merchants.uuid as merchant_uuid',
                'kobo_merchants.business_name as merchant_name',
                'kobo_merchants.created_at as merchant_created_at',
                'kobo_merchants.updated_at as merchant_updated_at',
                'kobo_order_items.id as order_item_id',
                'kobo_order_items.product_id',
                'kobo_order_items.quantity',
                'kobo_order_items.price',
                'merchant_products.product_name as product_name',
                'merchant_products.description as product_description',
                'merchant_product_images.image_path as product_image',
                'product_merchants.business_name as product_merchant_name'
            )
            ->orderBy('kobo_orders.created_at', 'desc')
            ->get();

        $userOrders = [];

        foreach ($orders as $order) {
            $orderId = $order->id;
            if (!isset($userOrders[$orderId])) {
                $userOrders[$orderId] = [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'merchant_id' => $order->merchant_id,
                    'status' => $order->status,
                    'amount' => $order->grand_total,
                    'delivery_address' => $order->delivery_address,
                    'order_created_at' => $order->created_at,
                    'order_updated_at' => $order->updated_at,
                    'merchant_uuid' => $order->merchant_uuid,
                    'merchant_name' => $order->merchant_name,
                    'merchant_created_at' => $order->merchant_created_at,
                    'merchant_updated_at' => $order->merchant_updated_at,
                    'items' => []
                ];
            }

            $userOrders[$orderId]['items'][] = [
                'order_item_id' => $order->order_item_id,
                'product_id' => $order->product_id,
                'quantity' => $order->quantity,
                'price' => $order->price,
                'product_name' => $order->product_name,
                'product_description' => $order->product_description,
                'product_image' => $order->product_image,
                'product_merchant_name' => $order->product_merchant_name // Add the product merchant name
            ];
        }

        return $this->sendResponse(array_values($userOrders), "User orders fetched successfully");
    }


    public function getUserRequest($userId)
    {
        // Query to join kobo_request_orders, kobo_merchants, kobo_request_order_items, merchant_short_stay, and merchant_short_stay_images
        $requests = DB::table('kobo_request_orders')
            ->join('kobo_merchants', 'kobo_request_orders.merchant_id', '=', 'kobo_merchants.uuid')
            ->join('kobo_request_order_items', 'kobo_request_orders.id', '=', 'kobo_request_order_items.kobo_request_order_id')
            ->join('merchant_short_stays', 'kobo_request_order_items.product_request_id', '=', 'merchant_short_stays.uuid')
            ->join('kobo_merchants as product_merchants', 'merchant_short_stays.kobo_merchant_id', '=', 'product_merchants.uuid')
            ->leftJoin('merchant_short_stay_images', 'merchant_short_stays.uuid', '=', 'merchant_short_stay_images.merchant_short_stay_id')
            ->where('kobo_request_orders.user_id', $userId)
            ->select(
                'kobo_request_orders.*',
                'kobo_merchants.uuid as merchant_uuid',
                'kobo_merchants.business_name as merchant_name',
                'kobo_merchants.created_at as merchant_created_at',
                'kobo_merchants.updated_at as merchant_updated_at',
                'kobo_request_order_items.id as request_item_id',
                'kobo_request_order_items.product_request_id',
                'kobo_request_order_items.quantity',
                'kobo_request_order_items.price',
                'merchant_short_stays.apartment_name as apartment_name',
                'merchant_short_stays.description as product_description',
                'merchant_short_stay_images.image_path as product_image',
                'product_merchants.business_name as product_merchant_name',
                'merchant_short_stays.room_type',
                'merchant_short_stays.location',
                'merchant_short_stays.duration',
                'merchant_short_stays.address as short_stay_address')->orderBy('kobo_request_orders.created_at', 'desc')
            ->get();

        // Prepare the response data
        $userRequests = [];

        foreach ($requests as $request) {
            $requestId = $request->id;
            if (!isset($userRequests[$requestId])) {
                $userRequests[$requestId] = [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id,
                    'merchant_id' => $request->merchant_id,
                    'status' => $request->status,
                    'amount' => $request->grand_total,
                    'delivery_address' => $request->delivery_address,
                    'request_created_at' => $request->created_at,
                    'request_updated_at' => $request->updated_at,
                    'merchant_uuid' => $request->merchant_uuid,
                    'merchant_name' => $request->merchant_name,
                    'merchant_created_at' => $request->merchant_created_at,
                    'merchant_updated_at' => $request->merchant_updated_at,
                    'items' => []
                ];
            }

            $userRequests[$requestId]['items'][] = [
                'request_item_id' => $request->request_item_id,
                'product_request_id' => $request->product_request_id,
                'quantity' => $request->quantity,
                'price' => $request->price,
                'apartment_name' => $request->apartment_name,
                'product_description' => $request->product_description,
                'product_image' => $request->product_image,
                'product_merchant_name' => $request->product_merchant_name,
                'room_type' => $request->room_type,
                'location' => $request->location,
                'duration' => $request->duration,
                'short_stay_address' => $request->short_stay_address
            ];
        }

        return $this->sendResponse(array_values($userRequests), "User requests with details fetched successfully");
    }


    // merchant_services.name
    public function getUsersOrderedServices($userId)
    {
        // Query to join kobo_service_orders, kobo_merchants, kobo_service_order_items, merchant_services, and merchant_service_images
        $services = DB::table('kobo_service_orders')
            ->join('kobo_merchants', 'kobo_service_orders.merchant_id', '=', 'kobo_merchants.uuid')
            ->join('kobo_service_order_items', 'kobo_service_orders.id', '=', 'kobo_service_order_items.kobo_service_order_id')
            ->join('merchant_services', 'kobo_service_order_items.product_service_id', '=', 'merchant_services.uuid')
            ->join('kobo_merchants as service_merchants', 'merchant_services.kobo_merchants_id', '=', 'service_merchants.uuid')
            ->leftJoin('merchant_service_images', 'merchant_services.uuid', '=', 'merchant_service_images.merchant_services_id')
            ->where('kobo_service_orders.user_id', $userId)
            ->select(
                'kobo_service_orders.*',
                'kobo_merchants.uuid as merchant_uuid',
                'kobo_merchants.business_name as merchant_name',
                'kobo_merchants.created_at as merchant_created_at',
                'kobo_merchants.updated_at as merchant_updated_at',
                'kobo_service_order_items.id as service_order_item_id',
                'kobo_service_order_items.product_service_id',
                'kobo_service_order_items.quantity',
                'kobo_service_order_items.price',
                'merchant_services.service_name as service_name',
                'merchant_services.description as product_description',
                'merchant_service_images.image_path as product_image',
                'service_merchants.business_name as service_merchant_name'
            )
            ->orderBy('kobo_service_orders.created_at', 'desc') // Order by creation date in descending order
            ->get();

        // Prepare the response data
        $userServices = [];

        foreach ($services as $service) {
            $serviceId = $service->id;
            if (!isset($userServices[$serviceId])) {
                $userServices[$serviceId] = [
                    'service_order_id' => $service->id,
                    'user_id' => $service->user_id,
                    'merchant_id' => $service->merchant_id,
                    'status' => $service->status,
                    'amount' => $service->grand_total,
                    'delivery_address' => $service->delivery_address,
                    'service_created_at' => $service->created_at,
                    'service_updated_at' => $service->updated_at,
                    'merchant_uuid' => $service->merchant_uuid,
                    'merchant_name' => $service->merchant_name,
                    'merchant_created_at' => $service->merchant_created_at,
                    'merchant_updated_at' => $service->merchant_updated_at,
                    'items' => []
                ];
            }

            $userServices[$serviceId]['items'][] = [
                'service_order_item_id' => $service->service_order_item_id,
                'product_service_id' => $service->product_service_id,
                'quantity' => $service->quantity,
                'price' => $service->price,
                'product_name' => $service->service_name,
                'product_description' => $service->product_description,
                'product_image' => $service->product_image,
                'service_merchant_name' => $service->service_merchant_name,
            ];
        }

        return $this->sendResponse(array_values($userServices), "User ordered services with details fetched successfully");
    }


    public function getUserMerchantOrders($merchantId)
    {
        $user = Auth::guard('api')->user();
        // Query to join kobo_orders, kobo_merchants, kobo_order_items, merchant_products, and merchant_product_images
        $orders = DB::table('kobo_orders')
            ->join('kobo_merchants', 'kobo_orders.merchant_id', '=', 'kobo_merchants.uuid')
            ->join('kobo_order_items', 'kobo_orders.id', '=', 'kobo_order_items.kobo_order_id')
            ->join('merchant_products', 'kobo_order_items.product_id', '=', 'merchant_products.uuid')
            ->join('kobo_merchants as product_merchants', 'merchant_products.kobo_merchants_id', '=', 'product_merchants.uuid')
            ->leftJoin('merchant_product_images', 'merchant_products.uuid', '=', 'merchant_product_images.merchant_products_id')
            ->where('kobo_orders.user_id', $user->id)
            ->where('kobo_orders.merchant_id', "=", $merchantId)
            ->select(
                'kobo_orders.*',
                'kobo_merchants.uuid as merchant_uuid',
                'kobo_merchants.business_name as merchant_name',
                'kobo_merchants.created_at as merchant_created_at',
                'kobo_merchants.updated_at as merchant_updated_at',
                'kobo_order_items.id as order_item_id',
                'kobo_order_items.product_id',
                'kobo_order_items.quantity',
                'kobo_order_items.price',
                'merchant_products.product_name as product_name',
                'merchant_products.description as product_description',
                'merchant_product_images.image_path as product_image',
                'product_merchants.business_name as product_merchant_name'
            )
            ->get();

        $userOrders = [];

        foreach ($orders as $order) {
            $orderId = $order->id;
            if (!isset($userOrders[$orderId])) {
                $userOrders[$orderId] = [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'merchant_id' => $order->merchant_id,
                    'status' => $order->status,
                    'amount' => $order->grand_total,
                    'delivery_address' => $order->delivery_address,
                    'order_created_at' => $order->created_at,
                    'order_updated_at' => $order->updated_at,
                    'merchant_uuid' => $order->merchant_uuid,
                    'merchant_name' => $order->merchant_name,
                    'merchant_created_at' => $order->merchant_created_at,
                    'merchant_updated_at' => $order->merchant_updated_at,
                    'items' => []
                ];
            }

            $userOrders[$orderId]['items'][] = [
                'order_item_id' => $order->order_item_id,
                'product_id' => $order->product_id,
                'quantity' => $order->quantity,
                'price' => $order->price,
                'product_name' => $order->product_name,
                'product_description' => $order->product_description,
                'product_image' => $order->product_image,
                'product_merchant_name' => $order->product_merchant_name // Add the product merchant name
            ];
        }

        return $this->sendResponse(array_values($userOrders), "User merchant history  fetched successfully");
    }




    //     public function getOrder($userId)
    // {
    //     $orders = DB::table('kobo_orders')
    //                 ->select('kobo_orders.*', 'merchants.*', 'kobo_merchants.*')
    //                 ->join('merchants', 'kobo_orders.user_id', '=', 'merchants.uuid')
    //                 ->join('kobo_merchants', 'merchants.user_id', '=', 'kobo_merchants.user_id')
    //                 ->where('kobo_orders.user_id', $userId)
    //                 ->whereNotNull('kobo_orders.id')
    //                 ->get();

    //     return $this->sendResponse($orders, "User Orders with Merchant and KoboMerchant Details");
    // }




    public function userCancelOrders($orderid)
    {
        $user= Auth::guard('api')->user();
        $order = KoboOrder::where('id', $orderid)->where('user_id', $user->id)->first();

        if (!$order) {
            return $this->sendError('error', 'Order not found');
        }
        $order->payment_status = 'unpaid';
        $order->status = 'decline';
        $sv = $order->save();

        if ($sv) {
            if ($order->payment_method == "wallet") {
                $user_wallet = UserWallet::where('user_id', $user->id)->first();
                $user_wallet->balance = $user_wallet->balance + $order->grand_total;
                $ws = $user_wallet->update();
                if ($ws) {
                    $authmsg = "Your order has been cancelled";
                    Notification::send($user, new KoboNotification(['title' => 'You Cancel an Order', 'message' => $authmsg, "order" => $order]));

                    $userMessage = 'Hello ' . $user->username . ', Your order has been cancelled';
                    $this->notice->sendPushNotification("01", 'Order Canceled', $userMessage, [$user->token], null, $order);

                    if (isset($user->email) && $user->emailVerified == 1) {
                        $msg = "you just cancelled an Order";
                        $subject = "Cancel Order Notification";
                        $this->kobo->anyEmailNotification($user->email, $user->name, $msg, $subject);
                    }

                    return $this->sendResponse($order, "Your order has been cancelled");
                }
            }

        } else {
            return $this->sendError('error', 'Oops!, Something went wrong!!');
        }
    }

    public function requestOrders()
    {
    }



    public function notDelivered($orderid)
    {
        $userid = Auth::guard('api')->user()->id;
        $order = KoboOrder::where('id', $orderid)->where('user_id', $userid)->first();

        if (!$order) {
            return $this->sendError('error', 'Order not found or unauthorized');
        }
        $order->status = 'processing';
        $sv = $order->save();

        if ($sv) {
            return $this->sendResponse($order, "Your order status has been changed to uncompleted");
        } else {
            return $this->sendError('error', 'Oops!, Something went wrong!!');
        }
    }

    public function rateMerchant(Request $request)
    {
        $check_order = KoboOrder::where('id', $request->order_id)->first();
        if ($check_order->status != 'completed') {
            return $this->sendError('warning', 'oOps, Order is still processing');
        } else if ($check_order->payment_status != 'paid') {
            return $this->sendError('warning', 'oOps, You can\'t review this order until you make payment');
        } else {
            $user_id = Auth::guard('api')->user()->id;
            $rating = new MerchantRating();
            $rating->kobo_order_id = $request->order_id;
            $rating->merchant_id = $check_order->merchant_id;
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

  public function getUserChatList()
{
    $user = Auth::guard('api')->user();
    $userId = $user->id;

    $chatlists = OrderChat::where('user_id', $userId)
                    ->orWhere('merchant_user_id', $userId)
                    ->get();

    if ($chatlists->isEmpty()) {
        return $this->sendError('error', 'You don\'t have a chat with anybody');
    }

    return $this->sendResponse($chatlists, "ChatLists");
}


    public function sendMessage(Request $request, $merchantid)
    {
        $user = Auth::guard('api')->user();
        $userId = Auth::guard('api')->user()->id;

        $merchantExists = KoboMerchant::where('uuid', $merchantid)->exists();
        if (!$merchantExists) {
            return $this->sendError('error', 'oops! this merchant does not exists');
        }

        $merchant = KoboMerchant::where('uuid', $merchantid)->first();
        $userMerchant = User::where('id', $merchant->user_id)->first();


        // Check if an order exists between the user and the merchant
        $orderExists = KoboOrder::where('id', $request->order_id)->where('merchant_id', $merchantid)->exists();
        if (!$orderExists) {
            return $this->sendError('error', 'You cannot chat with this merchant because you did not place any order from their business.');
        }
        // Find existing chat or create a new one
        $chat = OrderChat::where('kobo_order_id', $request->order_id)->where('kobo_merchant_id', $merchantid)->first();

        if (!$chat) {
            $chat = new OrderChat();
            $chat->kobo_order_id = $request->order_id;
            $chat->merchant_user_id =(int)$merchant->user_id;
            $chat->kobo_merchant_id = $merchantid;
            $chat->user_id = $userId;
        }
        // Update the chat message with the latest text
        $chat->message = $request->text;
        $chat->save();

        // Create a new chat message
        $newMessage = new OrderChatMessage();
        $newMessage->order_chat_id = $chat->id;
        $newMessage->user_entity_id = $userId;
        $newMessage->text = $request->text;
        $saved = $newMessage->save();

        // If the message was saved successfully
        if ($saved) {
            event(new OrderChatMessageCreated($newMessage));
            $this->notice->sendPushNotification("01", ' ' . $user->name. ' ', ''.$request->text.'', array($userMerchant->token), null, $newMessage);
            return $this->sendResponse($newMessage, 'Message Sent');
        } else {
            return $this->sendError('error', 'Oops, something went wrong!');
        }
    }




    public function orderChats(Request $request, $order_id)
    {
        $chats = OrderChat::where('kobo_order_id', $order_id)->get();

        if ($chats->isEmpty()) {
            return $this->sendError('error', 'Chat not found for the given order.');
        }
        $chatbox = [];
        foreach ($chats as $chat) {
            $chat_messages = OrderChatMessage::where('order_chat_id', $chat->id)->get();
            if ($chat_messages->isEmpty()) {
                return $this->sendError('error', 'No chat messages found for the given chat.');
            }

            // $chatbox= [
            //     "users-merchants-chats"=>$chat_messages
            // ];
        }


        return $this->sendResponse($chat_messages, "Chat Messages");
    }
}
