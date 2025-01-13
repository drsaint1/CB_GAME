<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends BaseController
{
    public function getLimit(Request $request, $type)
    {
        $limit = Setting::where('type', $type)->first();
        if(isset($limit)){
            return $this->sendResponse($limit, 'Limit Data');
        }
        else{
            return $this->sendError('error', 'oOps, Something went wrong!!');
        }
    }
}
