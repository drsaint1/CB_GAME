<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;

class GameController extends Controller
{
    public function savePoints(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
            'points' => 'required|integer|min:0',
        ]);

        $user = User::where('wallet_address', $request->wallet_address)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->increment('points', $request->points); // Increment the user's points
        return response()->json(['success' => true, 'total_points' => $user->points]);
    }

    public function logTransaction(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'transaction_id' => 'required|string',
            'action' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        // Store transaction in the database
        Transaction::create($validated);

        return response()->json(['message' => 'Transaction logged successfully'], 200);
    }
}
