<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KoboService;
use Illuminate\Http\Request;

class CallBackController extends Controller
{
    private $kobo;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
    }

    public function paymentNotification(Request $request){
        $inputs = json_decode($request->getContent(), true);
        \Log::info($inputs);
        $response = $this->kobo->monifyPaymentNotification($inputs);
        return  json_encode($response ?? null);
    }
    
    public function failedWithdrawal(Request $request){
        \Log::info($request);
        $inputs = json_decode($request->getContent(), true);
        
        $response = $this->kobo->monifyFailedWithdrawalNotification($inputs);
        return  json_encode($response ?? null);
    }
}
