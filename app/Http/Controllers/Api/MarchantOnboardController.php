<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\OrderChatMessageCreated;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\BusinessPromotionPlan;
use App\Models\KoboMerchant;
use App\Models\KoboMerchantOpening;
use App\Models\MerchantCategory;
use App\Models\PromotedBusiness;
use App\Models\UserWallet;
use App\Models\OrderChat;
use App\Models\KoboOrder;
use App\Models\User;
use App\Models\OrderChatMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PushNotification;
use Illuminate\Support\Str;
use App\Services\KoboService;
use App\Services\EmailService;
use App\Models\TableEntity;

class MarchantOnboardController extends BaseController
{

    private $notice;
    private $kobo;
    private $table;
    private $email;

    public function __construct(KoboService $kobo, EmailService $email)
    {
        $this->kobo = $kobo;
        $this->notice = new PushNotification();
        $this->table = new TableEntity();
        $this->email = $email;
    }


    public function add_business(Request $request)
    {
        if ($request->type == 'Data') {
            return $this->add_business_data($request);
        }
        if ($request->type == 'Info') {
            return $this->add_business_info($request);
        }
        if ($request->type == 'Location') {
            return $this->add_business_location($request);
        }
    }


    public function add_business_data($req)
    {
        $validator = Validator::make($req->all(), [
            'business_name' => 'required|unique:kobo_merchants',
            'delivery_type' => 'required',
            'delivery_time' => 'required',
            'payment_option' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        } else {
            $m = new KoboMerchant();
            $m->uuid = Str::uuid();
            $m->business_name = $req->business_name;
            $m->category_id = $req->category_id;
            $m->sub_category_id = $req->sub_category_id;
            $m->delivery_type = $req->delivery_type;
            $m->description = $req->description;
            $m->delivery_time = $req->delivery_time;
            $m->payment_option = $req->payment_option;
            $m->user_id = Auth::guard('api')->user()->id;
            $sm = $m->save();

            if ($sm) {
                return $this->sendResponse($m, 'Data Added Successfully');
            }
        }
    }

    public function add_business_info($req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required',
            'phone_number' => 'required',
            'business_logo' => 'required',
            'business_cover' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        } else {
            if ($req->has('business_logo')) {
                /*$bfile = $req->file('business_logo');
                $name = $bfile->getClientOriginalName();
                $destination_path = 'merchant/merchant_business_logo';
                $extension = $bfile->getClientOriginalExtension();
                $blfilename = $req->file('business_logo')->storeAs($destination_path, $name);*/

                $blfilename = Str::random(7) . '.' . $req->business_logo->extension();

                $req->business_logo->move(public_path('merchant_business_logo'), $blfilename);
            } else {
                $blfilename = 'Null';
            }

            if ($req->has('business_cover')) {
                /*$cfile = $req->file('business_cover');
                $namec = $cfile->getClientOriginalName();
                $destination_path_m = 'merchant/merchant_business_cover';
                $extensionc = $cfile->getClientOriginalExtension();
                $bcfilename = $req->file('business_cover')->storeAs($destination_path_m, $namec);*/
                $bcfilename = Str::random(7) . '.' . $req->business_cover->extension();

                $req->business_cover->move(public_path('merchant_business_cover'), $bcfilename);
            } else {
                $bcfilename = 'Null';
            }


            $m = KoboMerchant::where('user_id', Auth::guard('api')->user()->id)->where('business_name', $req->business_name)->first();
            $m->phone_number = $req->phone_number;
            $m->email = $req->email;
            $m->logo = $blfilename;
            $m->logo_cover = $bcfilename;
            $sm = $m->update();

            if ($sm) {
                return $this->sendResponse($m, 'Info Added Successfully');
            }
        }
    }

    public function add_business_location($req)
    {
        $validator = Validator::make($req->all(), [
            'state' => 'required',
            'city' => 'required',
            'lga' => 'required',
            'address' => 'required',
            'ip_address' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 'Validation Error');
        } else {
            $m = KoboMerchant::where('user_id', Auth::guard('api')->user()->id)->where('business_name', $req->business_name)->first();
            $m->location_state = $req->state;
            $m->location_city = $req->city;
            $m->location_lga = $req->lga;
            $m->business_address = $req->address;
            $m->ip_address = $req->ip_address;
            $m->longitude = $req->longitude;
            $m->latitude = $req->latitude;
            $sm = $m->update();
            if ($sm) {
                return $this->sendResponse($m, 'Business Created Successfully');
            }
        }
    }

    public function add_opening(Request $request)
    {
        $decode = json_encode($request->opening);
        foreach (json_decode($decode, true) as $item) {
            $op = new KoboMerchantOpening();
            $op->kobo_merchant_id = $request->merchant_id;
            $op->day = $item['day'];
            $op->open = $item['open'];
            $op->close = $item['close'];
            $sv = $op->save();
        }

        return $this->sendResponse($sv, 'Business hour added');
    }


    public function deleteOpening($bus_id)
    {
        $openings = KoboMerchantOpening::where('kobo_merchant_id', $bus_id)->get();
        if ($openings->isEmpty()) {
            return $this->sendError('error', 'Oops!, Opening hour not found.');
        }

        KoboMerchantOpening::where('kobo_merchant_id', $bus_id)->delete();
        return $this->sendResponse([], 'Opening hours deleted successfully');
    }

    public function updateOpening(Request $request, $openingId)
    {
        $validator = Validator::make($request->all(), [
            'day' => 'required|string',
            'open' => 'required|string',
            'close' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // $opening = KoboMerchantOpening::find($openingId);
        // $opening =KoboMerchantOpening::where('kobo_merchant_id', $openingId)->first();
        $opening = KoboMerchantOpening::where('id', $openingId)->first();

        if (!$opening) {
            return $this->sendError('error', 'Oops!, Opening hour not found.');
        }

        $opening->update([
            'day' => $request->input('day'),
            'open' => $request->input('open'),
            'close' => $request->input('close'),
        ]);

        return $this->sendResponse([], 'Opening hour updated successfully');
    }




    public function checkStoreLimit()
    {
        $user = Auth::guard('api')->user();
        $userBusinessCount = KoboMerchant::where('user_id', $user->id)->count();

        if ($userBusinessCount >= 2) {
            return $this->sendResponse(['disabled' => true], 'Business Limit Reached. Create button disabled.');
        } else {
            return  $this->sendResponse(['disabled' => false], 'Business creation allowed. Create button enabled.');
        }
    }


    public function businessMonthlyIncomeChart($businessId) {
        $businessExists = KoboMerchant::where('uuid', $businessId)->exists();

        if (!$businessExists) {
            return $this->sendError('error', 'Invalid or non-existent business ID.');
        }

        $currentMonth = date('n');

        $monthlyIncomes = array_fill(1, $currentMonth, 0);

        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        try {
            $koboOrders = DB::table('kobo_orders')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(grand_total) as total_income')
                )
                ->where('merchant_id', $businessId)
                ->whereYear('created_at', date('Y'))
                ->whereMonth('created_at', '<=', $currentMonth)
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->get();
        } catch (\Exception $e) {
            $koboOrders = collect();
        }

        foreach ($koboOrders as $order) {
            $monthlyIncomes[$order->month] += $order->total_income;
        }

        try {
            $koboRequestOrders = DB::table('kobo_request_orders')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(grand_total) as total_income')
                )
                ->where('merchant_id', $businessId)
                ->whereYear('created_at', date('Y'))
                ->whereMonth('created_at', '<=', $currentMonth)
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->get();
        } catch (\Exception $e) {
            $koboRequestOrders = collect();
        }

        foreach ($koboRequestOrders as $order) {
            $monthlyIncomes[$order->month] += $order->total_income;
        }

        try {
            $koboServiceOrders = DB::table('kobo_service_orders')
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(grand_total) as total_income')
                )
                ->where('merchant_id', $businessId)
                ->whereYear('created_at', date('Y'))
                ->whereMonth('created_at', '<=', $currentMonth)
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->get();
        } catch (\Exception $e) {
            $koboServiceOrders = collect();
        }

        foreach ($koboServiceOrders as $order) {
            $monthlyIncomes[$order->month] += $order->total_income;
        }

         // Transform the monthly incomes to use month names
         $monthlyIncomesWithNames = [];
         foreach ($monthlyIncomes as $monthNumber => $income) {
             $monthlyIncomesWithNames[$monthNames[$monthNumber]] = $income;
         }

         return $this->sendResponse($monthlyIncomesWithNames,"current business chart data");

        // for ($month = 1; $month <= $currentMonth; $month++) {
        //     if (!isset($monthlyIncomes[$month])) {
        //         $monthlyIncomes[$month] = 0;
        //     }
        // }
        // ksort($monthlyIncomes);
        // return $monthlyIncomes;
    }


    // public function replyChat(Request $request){
    //     $id = Auth::guard('api')->user()->id;
    //     $chat = OrderChat::where('kobo_order_id	', $request->order_id)->first();
    //     $new = new OrderChatMessage();
    //     $new->order_chat_id = $chat->id;
    //     $new->user_entity_id = $id;
    //     $new->text = $request->text;
    //     $sv = $new->save();

    //     if ($sv) {
    //         return $this->sendResponse($new, 'Message Sent');
    //     } else {
    //         return $this->sendError('error', 'Oops, Something wend wring!');
    //     }
    // }

    public function getMerchantOrder($merchantId)
    {
         // Query to join kobo_orders, kobo_merchants, kobo_order_items, merchant_products, and merchant_product_images
         $orders = DB::table('kobo_orders')
         ->join('kobo_merchants', 'kobo_orders.merchant_id', '=', 'kobo_merchants.uuid')
         ->join('kobo_order_items', 'kobo_orders.id', '=', 'kobo_order_items.kobo_order_id')
         ->join('merchant_products', 'kobo_order_items.product_id', '=', 'merchant_products.uuid')
         ->join('kobo_merchants as product_merchants', 'merchant_products.kobo_merchants_id', '=', 'product_merchants.uuid')
         ->leftJoin('merchant_product_images', 'merchant_products.uuid', '=', 'merchant_product_images.merchant_products_id')
         ->where('kobo_orders.user_id', $merchantId)
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

     return $this->sendResponse(array_values($userOrders), "merchants orders fetched successfully");
    }

    public function filterMerchantOrder($merchantId, $status)
    {
        $orders = KoboOrder::where('merchant_id', $merchantId)->where('status', $status)->orderBy('created_at', 'desc')->get();
        return $this->sendResponse($orders, $status . " transaction histories");
    }

    //     public function filterMerchantOrder($status)
    // {
    //     $user = Auth::guard('api')->user();
    //     $merchant = KoboMerchant::where('user_id', $user->id)->first();
    //     if (!$merchant) {
    //         return $this->sendError('error', 'Merchant not found');
    //     }
    //     $history = KoboOrder::where('merchant_id', $merchant->merchant_id)->where('status', $status)->orderBy('created_at', 'desc')->get();
    //     return $this->sendResponse($history, $status . " transaction histories");
    // }


    public function orderDetails($orderid)
    {
        $orders = KoboOrder::where('id', $orderid)->orderBy('created_at', 'desc')->get();
        return $this->sendResponse($orders, "order details");
    }

    public function cancelOrder($orderid)
    {
        $userid = Auth::guard('api')->user()->id;
        $order = KoboOrder::where('id', $orderid)->where('user_id', $userid)->first();

        if (!$order) {
            return $this->sendError('error', 'Order not found or unauthorized');
        }
        $order->status = 'cancelled';
        $sv = $order->save();

        if ($sv) {
            return $this->sendResponse($order, "You canceled this order");
        } else {
            return $this->sendError('error', 'Oops!, Something went wrong!!');
        }
    }

    public function sentOrder($orderid)
    {
        $userid = Auth::guard('api')->user()->id;
        $order = KoboOrder::where('id', $orderid)->where('user_id', $userid)->first();

        if (!$order) {
            return $this->sendError('error', 'Order not found');
        }
        $order->status = 'processing';
        $sv = $order->save();

        if ($sv) {
            return $this->sendResponse($order, "You canceled this order");
        } else {
            return $this->sendError('error', 'Oops!, Something went wrong!!');
        }
    }

    public function DeliveredOrder($orderid)
    {
        $userid = Auth::guard('api')->user()->id;
        $order = KoboOrder::where('id', $orderid)->where('user_id', $userid)->first();
        if (!$order) {
            return $this->sendError('error', 'Order not found or unauthorized');
        }
        $order->status = 'completed';
        $sv = $order->save();
        if ($sv) {
            return $this->sendResponse($order, "You canceled this order");
        } else {
            return $this->sendError('error', 'Oops!, Something went wrong!!');
        }
    }


    public function replyChat(Request $request, $userid)
    {
        $merchant = Auth::guard('api')->user();
        $merchantId = Auth::guard('api')->user()->id;
        $user = User::where('id', $userid)->first();
        $merchant = KoboMerchant::where('uuid', $merchantId)->first();
        $orderExists = KoboOrder::where('id', $request->order_id)->where('user_id', $userid)->exists();

        if (!$orderExists) {
            return $this->sendError('error', 'You cannot chat with this user because you did not place any order from their business.');
        }

        $chat = OrderChat::where('kobo_order_id', $request->order_id)->where('user_id', $userid)->first();

        if (!$chat) {
            $newChat = new OrderChat();
            $newChat->kobo_order_id = $request->order_id;
            $chat->merchant_user_id = $merchant->user_id;
            $newChat->kobo_merchant_id = $merchantId;
            $newChat->user_id = $userid;
           // $chat = $newChat;
        }

        $newChat->message = $request->text;
        $newChat->save();

        // Check if $chat is not null
        if ($chat) {
            // Create a new chat message
            $newMessage = new OrderChatMessage();
            $newMessage->order_chat_id = $chat->id;
            $newMessage->user_entity_id = $merchantId;
            $newMessage->text = $request->text;
            $saved = $newMessage->save();

            //  if the message was saved successfully
            if ($saved) {
                event(new OrderChatMessageCreated($newMessage));

                $user = User::where('id', $userid)->first();
                $this->notice->sendPushNotification("01", 'Order Message', $request->text, array($user->token), null, $newMessage);

                return $this->sendResponse($newMessage, 'Message Sent');
            } else {
                return $this->sendError('error', 'Oops, something went wrong!');
            }
        } else {
            return $this->sendError('error', 'Chat not found.');
        }
    }



    public function get_opening(Request $request, $bus_id)
    {
        $data = KoboMerchantOpening::where('kobo_merchant_id', $bus_id)->get();
        return $this->sendResponse($data, 'Business Opening Hour');
    }

    public function setDefaultBusiness(Request $request)
    {
        $mch = KoboMerchant::where('uuid', $request->business_uuid)->first();
        $mch->status = $request->status;
        $mch->update();
        return $this->sendResponse($mch, 'Status Updated');
        // $mch->status = 1;
        // $mch->update();
        // $oth = KoboMerchant::where('uuid', '!=', $request->uuid)->where('user_id', Auth::guard('api')->user()->id)->first();
        // $oth->status = 0;
        // $oth->update();
    }

    public function getPromotionPlan()
    {
        $bp = BusinessPromotionPlan::all();
        return $this->sendResponse($bp, 'Business Plans');
    }

    public function merchantBusiness()
    {
        $bd = KoboMerchant::where('user_id', Auth::guard('api')->user()->id)->with('openings')->get();
        $data = [
            "cover_image_url" => 'https://api.kobosquare.com/merchant_business_cover/',
            "cover_image_url" => 'https://api.kobosquare.com/merchant_business_logo/',
            "businesses" => $bd,

        ];
        return $this->sendResponse($data, 'Merchant Business');
    }


    public function checkIfPromoted($businessId)
    {
        $ifPromoted = PromotedBusiness::where('merchant_id', $businessId)->count();
        $PromotedBis = PromotedBusiness::where('merchant_id', $businessId)->first();
        if ($ifPromoted >= 1) {
            $bp = BusinessPromotionPlan::where('id', (int)$PromotedBis->business_promotion_plan_id)->first();
            $promotionDetails = [
                "last_payment" => $PromotedBis->created_at,
                "expiry_day" => $PromotedBis->expiry_date,
                "auto_renewal" => $PromotedBis->auto_renewal,
                "duration" => $bp->duration
            ];
            return $this->sendResponse($promotionDetails, 'business already promoted,promoted button disabled.');
            // return $this->sendResponse(['disabled' => true], 'business already promoted,promoted button disabled.');
        } else {
            return  $this->sendResponse(['disabled' => false], 'business not promoted,promoted button enabled.');
        }
    }

    public function promoteBusiness(Request $request)
    {
        $user = User::where('id', Auth::guard('api')->user()->id)->first();
        $m = KoboMerchant::where('user_id', Auth::guard('api')->user()->id)->where('uuid', $request->business_id)->first();
        $PromotedBis = PromotedBusiness::where('merchant_id',  $request->business_id)->first();
        $ifPromoted = PromotedBusiness::where('merchant_id', $request->business_id)->count();
        if ($ifPromoted >= 1) {
            return $this->sendResponse(['disabled' => true], 'business already promoted,promoted button disabled.');
        } else {
            if ($m->status == 1) {
                $bp = BusinessPromotionPlan::where('id', $request->promotion_id)->first();
                $mId = $m->uuid;
                $wallet = UserWallet::where('user_id', Auth::guard('api')->user()->id)->first();
                if ((int)$wallet->balance >= (int)$bp->price) {
                    $pm = new PromotedBusiness();
                    $pm->merchant_id = $mId;
                    $pm->business_promotion_plan_id = $request->promotion_id;
                    $pm->auto_renewal = $request->auto_renew;
                    if ($bp->duration == 'month') {
                        $pm->expiry_date = Carbon::now()->addDays(30);
                    }
                    if ($bp->duration == 'week') {
                        $pm->expiry_date = Carbon::now()->addDays(7);
                    }
                    if ($bp->duration == 'day') {
                        $pm->expiry_date = Carbon::now()->addDay(1);
                    }
                    if ($bp->duration == 'year') {
                        $pm->expiry_date = Carbon::now()->addDay(365);
                    }
                    $svp = $pm->save();
                    if ($svp) {

                        $dd = DB::table('user_wallets')->where('user_id', Auth::guard('api')->user()->id)->update([
                            'balance' => $wallet->balance - (int)$bp->price
                        ]);
                        $PromotedBusiness = PromotedBusiness::where('merchant_id',  $mId)->first();
                        $promotionDetails = [
                            "last_payment" => $PromotedBusiness->created_at,
                            "expiry_day" => $PromotedBusiness->expiry_date,
                            "auto_renewal" => $PromotedBusiness->auto_renewal,
                            "duration" => $bp->duration
                        ];

                        $trans_ref = $this->kobo->generateTransRef();

                        if (isset($user->email) && $user->emailVerified == 1) {
                            $msg = "The payment for promoting your business was successful, the promotion details is listed below";
                            $subject = "koboSquare Business Promotion";
                            // $type = "Order Payment";
                            $method = "Wallet";
                            $paymentStatus = "Successful";
                            $total =  $bp->price;
                            $proBusiness = $m->business_name;
                            $planName = $bp->plan_name;
                            $duration = $bp->duration;

                            // if ($result =
                             $this->email->promoteEmailNotification($user->email, $user->name, $msg, $subject, $trans_ref, $paymentStatus, $method, $proBusiness, $total, $planName, $duration, $PromotedBusiness->created_at, $PromotedBusiness->expiry_date);
                            // ) {
                            //     // if ($result['success']) {
                            //         return $this->sendResponse($promotionDetails, 'Business already promoted, promoted button disabled.');
                            //     } else {
                            //         return $this->sendError('error', $result['error']);
                            //     }
                            // } else {
                            //     return $this->sendError('error', 'Unable to send message');
                            // }
                        }

                        $dtat = [
                            'user_id' => $user->id,
                            'trans_ref' => $trans_ref,
                            'type' => 'Business Promotion',
                            'method' => "Wallet",
                            "status" => "Successful",
                            'amount' => $bp->price,
                            'from' => $user->id,
                            'to' => "Kobosquare"
                        ];
                        $this->table->insertNewEntry('transactions', 'id', $dtat);

                        // $wallet->balance = $wallet->balance - (int)$bp->price;
                        // $wallet->update();

                        return $this->sendResponse('success', 'You have successfully promoted your business');
                    }
                } else {
                    return $this->sendError('error', 'Oops!, You have insufficient wallet balance, please fund your wllet to continue.');
                }
            } else {
                return $this->sendError('error', 'Oops!, This business is not your default business, please set as default!');
            }
        }
    }
}
