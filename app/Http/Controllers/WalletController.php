<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Transaction;

class WalletController extends Controller
{
    public function getWallet($id)
    {
        try {
            $wallet = Wallet::where('user_id', $id)->firstOrFail();
            return response()->json($wallet);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Wallet not found for user ID: $id");
            return response()->json(['message' => 'Wallet not found'], 404);
        }
    }

    public function withdrawPoints(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string|exists:users,wallet_address',
            'points' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($request) {
            try {
                $user = User::where('wallet_address', $request->wallet_address)
                    ->lockForUpdate()
                    ->firstOrFail();

                
                // Update wallet
                $user->points -= $request->points;;
                $user->save();

                // Create transaction
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'transaction_id' => $this->generateTransactionId(),
                    'action' => 'withdrawal',
                    'amount' => -$request->points,
                ]);

             

                return response()->json([
                    'message' => 'Points withdrawn successfully',
                    'transaction_id' => $transaction->transaction_id,
                    'new_balance' => $user->points
                ]);

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::error("Resource not found: " . $e->getMessage());
                return response()->json(['message' => 'Resource not found'], 404);
            } catch (\Exception $e) {
                Log::error("Withdrawal failed: " . $e->getMessage());
                DB::rollBack();
                return response()->json(['message' => 'Withdrawal processing failed'], 500);
            }
        });
    }

    private function generateTransactionId(): string
    {
        return implode('-', [
            'tx',
            now()->format('YmdHis'),
            strtoupper(bin2hex(random_bytes(4)))
        ]);
    }
}
