<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\UserWallet;
use App\Models\User;
use App\Services\BookingService;
use App\Services\KoboService;
use App\Services\PushNotification;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\BusinessPromotionPlan;
use App\Models\PromotedBusiness;

class SubscriptionController extends BaseController
{
    // public function getLastPayment(Request $request)
    // {
    //     $lastPayment = Transaction::where('user_id', $request->user()->id)->where('type', 'promotion')->orderByDesc('created_at')->first();
    //     return response()->json($lastPayment);
    // }

    public function getNextPayment(Request $request)
    {
        $user = Auth::guard('api')->user();
        $lastPayment = DB::table('transactions')
        ->where('user_id', $user->id)
        ->where('type', 'promotion')
        ->orderByDesc('created_at')
        ->first();

        if (!$lastPayment) {
            return response()->json(['error' => 'No previous payment found'], 404);
        }
        $lastPaymentDate = Carbon::parse($lastPayment->created_at);
        $nextPaymentDate = $lastPaymentDate->addDays(7);
        $days=[
            'last_payment_date' => $lastPaymentDate->toDateString(),
            'next_payment_date' => $nextPaymentDate->toDateString()
        ];
        return $this->sendResponse($days, "next and last payment days");

    }

    public function autoRenewSubscriptions()
    {
        $promotedBusinesses = PromotedBusiness::where('auto_renewal', 1)->whereDate('next_renewal_date', now())->get();
        $wallets = UserWallet::whereIn('merchant_id', $promotedBusinesses->pluck('merchant_id'))->get()->keyBy('merchant_id');
        // Start a database transaction
        DB::beginTransaction();

        try {
            foreach ($promotedBusinesses as $promotedBusiness) {
                $merchantId = $promotedBusiness->merchant_id;
                $wallet = $wallets[$merchantId] ?? null;

                if ($wallet && $wallet->balance >= $promotedBusiness->price) {
                    // Deduct the subscription amount from the user's wallet balance
                    $wallet->balance -= $promotedBusiness->price;
                    $wallet->save();

                    // Update the next renewal date
                    $promotedBusiness->next_renewal_date = now()->addDays(7);
                    $promotedBusiness->save();
                    Transaction::create([
                        'user_id' => $merchantId,
                        'amount' => $promotedBusiness->price,
                        'type' => 'promotion'
                    ]);
                    return $this->sendResponse("success", "Auto-renewal successful for user {$merchantId}");
                  //  Log::info("Auto-renewal successful for user {$merchantId}");
                } else {
                    // User has insufficient balance in the wallet
                    return $this->sendError('error',"Insufficient wallet balance for user {$merchantId}. Auto-renewal failed.");
                //    Log::error("Insufficient wallet balance for user {$merchantId}. Auto-renewal failed.");
                }
            }
            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('error',"Auto-renewal failed: {$e->getMessage()}");
         //   Log::error("Auto-renewal failed: {$e->getMessage()}");
        }
    }


    public function toggleAutoRenewal(Request $request)
    {
        $businessOnAutorenewal = PromotedBusiness::where('merchant_id', $request->merchantid)->first();
        if (!$businessOnAutorenewal) {
            return $this->sendError('error', 'merchant not found');
        }
        $businessOnAutorenewal->auto_renewal = $request->auto_renewal;
        $sv = $businessOnAutorenewal->save();
        if ($sv) {
            return $this->sendResponse("success", "Auto-renewal status updated successfully.");
        } else {
            return $this->sendError('error', 'Oops!, Something went wrong!!');
        }
    }

    public function renewSubscription(Request $request)
    {
        $user = Auth::guard('api')->user();
        // Retrieve user's promoted business
        $promotedBusiness = PromotedBusiness::where('merchant_id', $request->merchantid)->first();

        if (!$promotedBusiness) {
            return $this->sendError('error','No active promoted business found.');
        }
        $businessPlan = BusinessPromotionPlan::where('id', $promotedBusiness->business_promotion_plan_id)->first();
        $wallet = UserWallet::where('user_id', $user->id)->first();

        if (!$wallet || $wallet->balance < $businessPlan->price) {
            return $this->sendError('error','Insufficient wallet balance.');
        }

        $wallet->balance -= $businessPlan->price;
        $wallet->save();
        $promotedBusiness->expiry_date = now()->addDays(7);
        $promotedBusiness->update();
        Transaction::create([
            'user_id' => $user->id,
            'amount' => $businessPlan->price,
            'type' => 'promotion'
        ]);

        return $this->sendResponse("success", 'Subscription renewed successfully.');
    }
}
