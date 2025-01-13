<?php

namespace App\Http\Controllers\Logistics;

use App\Models\Logistic;
use App\Models\User_kobo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\CompanyOnboard;

class RegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('logistics/register');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('logistics/register');
    }

    private function company_no(){
        $code = 'KBC-' . \Str::random(7) . '-' . \Str::random(4) . '-' . \Str::random(8);
        return $code;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'companyName' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $createData = [
            'company_no' => $this->company_no(),
            'company_name' => $request->companyName,
            'phone_number' => $request->phone,
            'email' => $request->email,
            'status' => '0',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        DB::beginTransaction();
        $logistic = Logistic::create($createData);

        if (!$logistic) {
            DB::rollBack();
            return redirect()->back()->with('error','Unable to create company. Please try again.');
        }

        $usersKobo = [
            'username' => $request->email,
            'password' => Hash::make($request->password),
            'user_table_id' => $logistic->id,
            'user_type' => 'logistics',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => '0'
        ];
        if(!User_kobo::create($usersKobo)){
            DB::rollBack();
            return redirect()->back()->with('error','Unable to create company. Please try again.');
        }

        try{
            Mail::to($request->email)->send(new CompanyOnboard(
                $request->companyName,
                Crypt::encryptString($request->email)
            ));
        } catch(\Exception $e){
            echo 'Error - '.$e;
        }

        DB::commit();
        return redirect()->back()->with('success','Your have successfully register.Kindly Check your email for verification');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function onboard_account(Request $request, $token){
        $token = decrypt($token);
        $userDetails = User_kobo::where('username', $token)->firstOrFail();
        if(!$request->hasValidSignature() && !$userDetails){
            return abort(401);
        }
        $userType = $userDetails->user_type;
        $query = "update users_kobo,$userType set users_kobo.status = '1',$userType.status='1' where users_kobo.user_table_id = $userType.id and users_kobo.id = ? and users_kobo.user_type=?";
        $affected = DB::update($query, [$userDetails->id, $userType]);
        if(!$affected){
            abort(404);
        }
        return view('logistics.welcome');
    }
}
