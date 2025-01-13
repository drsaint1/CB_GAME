<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User_kobo;

class LoginController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('logistics/login');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('logistics/login');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User_kobo::where('username', $request->email)->firstOrFail();
        if(!$user){
            return back()->withErrors([
                'email' => 'The provided credentials do not match our record.',
            ])->onlyInput('email');
        }

        if($user->status != '1'){
            return back()->withErrors([
                'email' => 'Your account has not been verified',
            ])->onlyInput('email');
        }

        $loginCredentials = ['username' => $request->email, 'password' => $request->password];
        if (Auth::guard('user_kobo')->attempt($loginCredentials)) {
            $request->session()->regenerate();
    
            return redirect()->route('logistics.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');

    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function forget_password(){
        
    }

    public function forgetPassword(){
        return view('logistics/forgot-password');
    }
}
