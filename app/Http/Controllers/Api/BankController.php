<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\TableEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends BaseController
{
    private $table;

    public function __construct()
    {
        $this->table = new TableEntity();
    }
    public function getBanks()
    {
        $banks = DB::table('bank_entities')
            ->select('bank_code', 'bank_name')->where('status', 'active')->get();

        return $this->sendResponse($banks, 'All Active Bank');
    }

    public function getVirtualBanks()
    {
        $user = \Auth::guard('api')->user();
        $account1 = $user->v_account_bank;
        $account_1 = preg_replace('/\s+\S+$/', '', $account1);
        $account2 = $user->v_account_2_bank;
        $account_2 = preg_replace('/\s+\S+$/', '', $account2);
        $account = [

            $account_1 => [
                "AccountNum" => $user->v_account_num,
                "AccountName" => $user->v_account_name,
                "BankName" => $user->v_account_bank,
            ],
            $account_2 => [
                "AccountNum" => $user->v_account_2_num,
                "AccountName" => $user->v_account_2_name,
                "BankName" => $user->v_account_2_bank,
            ]
        ];

        return $this->sendResponse($account, 'All Active Virtual Account');

    }

    public function getUserBanks()
    {
        $user = \Auth::guard('api')->user();
        $acct = \DB::table('user_banks')->where('user_id', $user->id)->get(['id', 'account_name', 'account_number', 'bank_name']);
        return $this->sendResponse($acct, 'All User Added Bank');
    }
}