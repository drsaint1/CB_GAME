<?php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function getUserDetails(Request $request)
    {
        $walletAddress = $request->query('walletAddress');

        // Validate if walletAddress is provided
        if (!$walletAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet address is required.',
            ], 400);
        }

        // Fetch user details
        $user = User::where('wallet_address', $walletAddress)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'fullname' => $user->fullname,
                'email' => $user->email,
                'username' => $user->username,
                'wallet_address' => $user->wallet_address,
                'points' => $user->points,
                'usernameMissing' => empty($user->username), // Flag for missing username
            ],
        ]);
    }


    public function connectWallet(Request $request)
    {

        $request->validate([
            'wallet_address' => 'required|string',
        ]);

        $walletAddress = $request->wallet_address;

        // Check if the wallet already exists
        $user = User::where('wallet_address', $walletAddress)->first();

        if ($user) {
            return response()->json(['exists' => true, 'user' => $user,]);
        }

        // If wallet is new, create an entry without a username
        $user = User::create([
            'wallet_address' => $walletAddress,
        ]);

        return response()->json(['exists' => false, 'user' => $user,]);
    }


    public function setUsername(Request $request)
    {
        $request->validate([
            'walletAddress' => 'required|string',
            'username' => 'required|string|min:3|max:50',
        ]);

        $walletAddress = $request->input('walletAddress');
        $username = $request->input('username');

        $user = User::where('wallet_address', $walletAddress)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Update the user's username
        $user->username = $username;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Username updated successfully.',
        ]);
    }


    // public function setUsername(Request $request)
    // {
    //     $request->validate([
    //         'wallet_address' => 'required|string',
    //         'username' => 'required|string|unique:users,username',
    //     ]);

    //     $user = User::where('wallet_address', $request->wallet_address)->first();

    //     if (!$user) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }

    //     $user->username = $request->username;
    //     $user->save();

    //     return response()->json(['user' => $user]);
    // }

    public function saveUser(Request $request)
    {
        $request->validate([
            'wallet' => 'required|unique:users,wallet',
            'username' => 'required|unique:users,username',
            'referral' => 'nullable|exists:users,username',
        ]);

        $user = User::create([
            'wallet' => $request->wallet,
            'username' => $request->username,
            'points' => 0,
        ]);

        if ($request->referral) {
            $referrer = User::where('username', $request->referral)->first();
            $referrer->increment('points', 50); // Bonus points
            Referral::create([
                'referrer_id' => $referrer->id,
                'referee_id' => $user->id,
            ]);
        }

        return response()->json(['message' => 'User saved successfully']);
    }

    // public function dashboard()
    // {
    //     $user = auth()->user();
    //     $referrals = Referral::where('referrer_id', $user->id)->count();

    //     return response()->json([
    //         'wallet' => $user->wallet,
    //         'points' => $user->points,
    //         'referrals' => $referrals,
    //     ]);
    // }
}
