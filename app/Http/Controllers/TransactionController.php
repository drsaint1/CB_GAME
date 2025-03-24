<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Fetch all transactions for a given wallet address
     */
    public function getTransactions(Request $request)
    {
        // Get wallet address from query parameters
        $walletAddress = $request->query('wallet_address');

        // Debugging logs
        \Log::info("Fetching transactions for wallet address: " . $walletAddress);

        // Validate request
        if (!$walletAddress) {
            return response()->json(['message' => 'Wallet address is required'], 400);
        }

        // Fetch user using wallet address
        $user = User::where('wallet_address', $walletAddress)->first();

        if (!$user) {
            \Log::error("User not found for wallet address: " . $walletAddress);
            return response()->json(['message' => 'User not found'], 404);
        }

        // Fetch transactions for the user
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }

    /**
     * Create a new transaction for a user
     */
    public function createTransaction(Request $request)
    {
        // Debugging logs
        Log::info("Creating transaction for wallet address: " . $request->wallet_address);

        // Validate request
        $request->validate([
            'wallet_address' => 'required|string',
            'action' => 'required|string', // Example: "deposit" or "withdrawal"
            'amount' => 'required|numeric',
        ]);

        // Fetch user using wallet address
        $user = User::where('wallet_address', $request->wallet_address)->first();

        if (!$user) {
            Log::error("User not found for wallet address: " . $request->wallet_address);
            return response()->json(['message' => 'User not found'], 404);
        }

        // Generate a unique transaction ID
        $transactionId = uniqid("tx_", true);

        // Create the transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_id' => $transactionId,
            'action' => $request->action,
            'amount' => $request->amount,
        ]);

        Log::info("Transaction created successfully", [
            'wallet_address' => $request->wallet_address,
            'transaction_id' => $transactionId,
            'action' => $request->action,
            'amount' => $request->amount
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'transaction' => $transaction
        ]);
    }
}
