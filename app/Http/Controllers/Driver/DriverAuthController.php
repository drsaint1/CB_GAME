<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverVehicle;
use App\Services\KoboService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriverAuthController extends Controller
{
    private $kobo;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
    }

    public function driverUserReg(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'surname' => 'required',
            'email' => 'required|unique:drivers',
            'phone' => 'required|unique:drivers',
            'gender' => 'required',
            'password' => 'required',
        ], [
            'first_name.required' => 'First Name is required',
            'surname.required' => 'Surname is required'
        ]);

        $da = new Driver();
        $da->first_name = $request->first_name;
        $da->surname = $request->surname;
        $da->email = $request->email;
        $da->phone = $request->phone;
        $da->gender = $request->gender;
        $da->password = bcrypt($request->password);
        $save = $da->save();
        if ($save) {
            return redirect(route('driver.verify_num'));
        } else {
            return redirect()->back()->with('error', 'Error Occurred');
        }

    }

    public function verify_num(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'password' => 'required'
        ]);

        $phone = $request->phone;

        $dr = Driver::where('phone', $request->phone)->first();
        if (!isset($dr)) {
            return redirect()->back()->with('error', 'Phone number does not exist. Please go back to join our driver to get started');
        }
        else if(isset($dr) && !Hash::check($request->password, $dr->password)){
            return redirect()->back()->with('error', 'Invalid Phone/Password');
        }
        else if(isset($dr) && $dr->phoneVerified == 1){
            return redirect()->back()->with('error', 'Phone number already verified. Please login on mobile app to continue');
        }
        else if(isset($dr) && Hash::check($request->password, $dr->password) && $dr->phoneVerified == 0){
            $this->kobo->phone_send_verification($request->phone);
            return view('driver.otp_verify', compact('phone'));
        }
        else{
            return redirect()->back()->with('error', 'Opps!. Something is wrong!!');
        }
    }

    public function verify_otp_driver(Request $request)
    {
        $otp = $request->one.''.$request->two.''.$request->three.''.$request->four.''.$request->five.''.$request->six;
        $dr = Driver::where('phone', $request->phone)->first();
        //dd($otp, $request->phone);
        $verify = $this->kobo->verify_phone($otp, $request->phone);
        if ($verify->valid) {
            $dr->phoneVerified = 1;
            $dr->update();
            Auth::guard('driver')->login($dr);
            return redirect(route('driver.almost_in'));
        } else {
            return redirect()->back()->with('error', 'Oops! Invalid Otp code!!');
        }
    }

    public function driverReg(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'manufacturer' => 'required',
            'model' => 'required',
            'year' => 'required',
            'vehicle_plate_number' => 'required',
            'num_of_seat' => 'required',
            'vehicle_colour' => 'required',
        ]);

        $dv = new DriverVehicle();
        $dv->driver_id = Auth::guard('driver')->user()->id;
        $dv->type = $request->type;
        $dv->manufacturer = $request->manufacturer;
        $dv->model = $request->model;
        $dv->year = $request->year;
        $dv->plate_number = $request->vehicle_plate_number;
        $dv->num_of_seat = $request->num_of_seat;
        $dv->color = $request->vehicle_colour;

        $sv = $dv->save();
        if($sv){
            return redirect(route('driver.just_to_go'));
        }
        else{
            return redirect()->back()->with('error', 'Opps!. Something is wrong!!');
        }
    }

    public function doc_upload(Request $request)
    {
        $request->validate([
            'driver_licence' => 'required|max:2048',
            'insurance_document' => 'required|max:2048',
            'private_hire_licence' => 'required|max:2048',
        ]);

        $dr_lc = Str::random(5).'.'.$request->driver_licence->extension();

        $request->driver_licence->move(public_path('driver_document'), $dr_lc);

        $in_dc = Str::random(5).'.'.$request->insurance_document->extension();

        $request->insurance_document->move(public_path('driver_document'), $in_dc);

        $ph_lc = Str::random(5).'.'.$request->private_hire_licence->extension();

        $request->private_hire_licence->move(public_path('driver_document'), $ph_lc);

        $ddc = new DriverDocument();
        $ddc->driver_id = Auth::guard('driver')->user()->id;
        $ddc->driver_licence = $dr_lc;
        $ddc->insurance_document = $in_dc;
        $ddc->private_hire_licence = $ph_lc;

        $sv = $ddc->save();

        if($sv){
            return redirect(route('driver.driver_done'));
        }
        else{
            return redirect()->back()->with('error', 'Opps!. Something is wrong!!');
        }

    }
}
