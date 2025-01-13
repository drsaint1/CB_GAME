<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{

    public function getReferrals($wallet)
    {
        $referrals = Referral::where('referrer_wallet', $wallet)
            ->join('users', 'referrals.referred_wallet', '=', 'users.wallet_address') // Inner join with the users table
            ->select(
                'referrals.*', // Select all columns from the referrals table
                'users.username as username', // Select the user's name
                'users.points as userPoints', // Select the user's email
                'users.created_at as user_joined_date' // Select user's created date
            )
            ->get();

        return response()->json($referrals);
    }




    public function register(Request $request)
    {
        // Validate input
        $request->validate([
            'wallet_address' => 'required|string',
            'referrer_wallet' => 'nullable|string',
        ]);

        // Check if the wallet address already exists
        $existingUser = User::where('wallet_address', $request->wallet_address)->first();

        if ($existingUser) {
            return response()->json([
                'message' => 'Wallet address is already registered.',
            ], 400); // 400 Bad Request
        }


        // Check if the referrer wallet address exists in the `users` table
        if ($request->referrer_wallet && !User::where('wallet_address', $request->referrer_wallet)->exists()) {
            return response()->json([
                'message' => 'Referrer address does not exist',
            ], 400); // 400 Bad Request
        }

        // Check if the referred user already exists in the `referrals` table
        if ($request->referrer_wallet) {
            $alreadyReferred = Referral::where('referrer_wallet', $request->referrer_wallet)
                ->where('referred_wallet', $request->wallet_address)
                ->exists();

            if ($alreadyReferred) {
                return response()->json([
                    'message' => 'Already referred by someone',
                ], 400); // 400 Bad Request
            }
        }

        // Create the new user
        $user = User::create([
            'wallet_address' => $request->wallet_address,
            'points' => 0,
        ]);

        // Debugging the referral creation
        if ($request->referrer_wallet && $request->referrer_wallet !== $request->wallet_address) {
            // Log or output the values
            // return response()->json([
            //     'message' => 'Debugging values',
            //     'referrer_wallet' => $request->referrer_wallet,
            //     'referred_wallet' => $request->wallet_address,
            // ]);

            // Attempt to create the referral
            Referral::create([
                'referrer_wallet' => $request->referrer_wallet,
                'referred_wallet' => $request->wallet_address,
                'bonus' => '20',
            ]);

            // Increment the referrer's points
            User::where('wallet_address', $request->referrer_wallet)->increment('points', 20);
        }


        return response()->json([
            'message' => 'success',
            'wallet_address' => $request->wallet_address,
        ]);
    }
}
