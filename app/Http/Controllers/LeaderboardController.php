<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leaderboard;
use App\Models\User;
use App\Models\Wallet;

class LeaderboardController extends Controller
{

    // public function index()
    // {
    //     $users = User::orderBy('points', 'desc')->take(10)->get(['username', 'points']);
    //     return response()->json($users);
    // }

    public function index($walletAddress)
    {
        // Find the user by their wallet address
        $user = User::where('wallet_address', $walletAddress)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Fetch top 10 users by points
        $leaderboard = User::orderBy('points', 'desc')
            ->take(10)
            ->get(['username', 'points']);

        // Fetch all users to calculate the current user's rank
        $allUsers = User::orderBy('points', 'desc')->get(['wallet_address', 'points']);
        $currentUserRank = $allUsers->search(function ($userItem) use ($walletAddress) {
            return $userItem->wallet_address === $walletAddress;
        });

        // Prepare rank data
        $rankData = $currentUserRank !== false
            ? [
                'rank' => $currentUserRank + 1,
                'points' => $allUsers[$currentUserRank]->points,
                'totalUsers' => $allUsers->count(),
            ]
            : null;

        return response()->json([
            'leaderboard' => $leaderboard,
            'currentUserRank' => $rankData,
        ]);
    }


}
