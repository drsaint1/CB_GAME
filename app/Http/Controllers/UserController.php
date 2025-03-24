<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Fetch user details by wallet address
     */
    public function getUserDetails(Request $request)
    {
        // Set custom error handler to suppress deprecated warnings
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // Suppress deprecation warnings (E_DEPRECATED and E_USER_DEPRECATED)
            if ($errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
                return true; // Suppress the warning
            }
            return false; // Pass other errors to default handler
        });

        try {
            $walletAddress = $request->input('wallet_address');

            if (!$walletAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet address is required.',
                ], 400);
            }

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
                    'usernameMissing' => empty($user->username),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user details.',
            ], 500);
        } finally {
            // Reset the custom error handler to the default one
            restore_error_handler();
        }
    }

    /**
     * Connect wallet address
     */
    public function connectWallet(Request $request)
    {
        // Set custom error handler to suppress deprecated warnings
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if ($errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
                return true; // Suppress the warning
            }
            return false;
        });

        try {
            $request->validate([
                'wallet_address' => 'required|string',
            ]);

            $walletAddress = $request->wallet_address;

            $user = User::where('wallet_address', $walletAddress)->first();

            if ($user) {
                return response()->json(['exists' => true, 'user' => $user]);
            }

            $user = User::create(['wallet_address' => $walletAddress]);

            return response()->json(['exists' => false, 'user' => $user]);
        } catch (\Exception $e) {
            Log::error('Error connecting wallet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while connecting the wallet.',
            ], 500);
        } finally {
            // Reset the custom error handler to the default one
            restore_error_handler();
        }
    }

    /**
     * Set username for a user
     */
    public function setUsername(Request $request)
    {
        // Set custom error handler to suppress deprecated warnings
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if ($errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
                return true; // Suppress the warning
            }
            return false;
        });

        try {
            $request->validate([
                'wallet_address' => 'required|string',
                'username' => 'required|string|min:3|max:50|unique:users,username',
            ]);

            $user = User::where('wallet_address', $request->wallet_address)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $user->username = $request->username;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Username updated successfully.',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Error setting username: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while setting the username.',
            ], 500);
        } finally {
            // Reset the custom error handler to the default one
            restore_error_handler();
        }
    }

}
