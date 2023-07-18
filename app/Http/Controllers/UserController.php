<?php
namespace App\Http\Controllers;
use App\Helper\JWTToken;
use App\Mail\OTPMail;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserController extends Controller
{

    function LoginPage():View{
        return view('pages.auth.login-page');
    }
    function RegistrationPage():View{
        return view('pages.auth.registration-page');
    }
    function SendOtpPage():View{
        return view('pages.auth.send-otp-page');
    }
    function VerifyOTPPage():View{
        return view('pages.auth.verify-otp-page');
    }
    function ResetPasswordPage():View{
        return view('pages.auth.reset-pass-page');
    }

    function UserRegistration(Request $request){
        try {
            User::create([
                'firstName' => $request->input('firstName'),
                'lastName' => $request->input('lastName'),
                'email' => $request->input('email'),
                'mobile' => $request->input('mobile'),
                'password' => $request->input('password'),
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'User Registration Successfully'
            ],200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User Registration Failed ! From Back-End'
            ],400);

        }
    }

    function UserLogin(Request $request){
       $count=User::where('email','=',$request->input('email'))
            ->where('password','=',$request->input('password'))
            ->count();

       if($count==1){
           // User Login-> JWT Token Issue
           $token=JWTToken::CreateToken($request->input('email'));
           return response()->json([
               'status' => 'success',
               'message' => 'User Login Successful',
           ],200)->cookie('token',$token,60*60*24);
       }
       else{
           return response()->json([
               'status' => 'failed',
               'message' => 'unauthorized'
           ],401);

       }

    }
    function SendOTPCode(Request $request){

        $email=$request->input('email');
        $otp=rand(1000,9999);
        $count=User::where('email','=',$email)->count();

        if($count==1){
            // OTP Email Address
            Mail::to($email)->send(new OTPMail($otp));
            // OTO Code Table Update
            User::where('email','=',$email)->update(['otp'=>$otp]);

            return response()->json([
                'status' => 'success',
                'message' => '4 Digit OTP Code has been send to your email !'
            ],200);
        }
        else{
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized'
            ],401);
        }
    }
    function VerifyOTP(Request $request){
        $email=$request->input('email');
        $otp=$request->input('otp');
        $count=User::where('email','=',$email)
            ->where('otp','=',$otp)->count();
        if($count==1){
            // Database OTP Update
            User::where('email','=',$email)->update(['otp'=>'0']);

            // Pass Reset Token Issue
            $token=JWTToken::CreateTokenForSetPassword($request->input('email'));

            return response()->json([
                'status' => 'success',
                'message' => 'OTP Verification Successful',
            ],200)->cookie('token',$token,60*60*24);
        }
        else{
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized'
            ],401);
        }
    }
    function ResetPassword(Request $request){

        try{
            $email=$request->header('email');
            $password=$request->input('password');
            User::where('email','=',$email)->update(['password'=>$password]);
            // Remove Cookie...
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful',
            ],200);

        }catch (Exception $exception){
            return response()->json([
                'status' => 'fail',
                'message' => 'Something Went Wrong',
            ],200);
        }

    }

}
