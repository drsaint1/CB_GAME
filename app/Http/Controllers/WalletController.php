<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;

class WalletController extends Controller
{

    public function getWallet($id)
    {
        $wallet = Wallet::where('user_id', $id)->first();
        return response()->json($wallet);
    }

    public function withdrawPoints(Request $request)
    {
        $request->validate(['points' => 'required|integer']);

        $wallet = Wallet::where('user_id', $request->user()->id)->first();

        if ($wallet->points < $request->points) {
            return response()->json(['message' => 'Insufficient points'], 400);
        }

        $wallet->update([
            'points' => $wallet->points - $request->points,
            'withdrawn' => $wallet->withdrawn + $request->points
        ]);

        return response()->json(['message' => 'Points withdrawn successfully']);
    }


    // public function withdrawPoints(Request $request)
    // {
    //     $user = auth()->user();
    //     $amount = $request->input('amount');

    //     if ($user->wallet->total_points < $amount) {
    //         return response()->json(['error' => 'Insufficient points'], 400);
    //     }

    //     // Deduct points
    //     $balanceBefore = $user->wallet->total_points;
    //     $user->wallet->total_points -= $amount;
    //     $user->wallet->withdrawn_points += $amount;
    //     $user->wallet->save();

    //     // Log transaction
    //     Transaction::create([
    //         'user_id' => $user->id,
    //         'transaction_type' => 'withdrawal',
    //         'amount' => -$amount,
    //         'balance_before' => $balanceBefore,
    //         'balance_after' => $user->wallet->total_points
    //     ]);

    //     return response()->json(['message' => 'Withdrawal successful']);
    // }
}
