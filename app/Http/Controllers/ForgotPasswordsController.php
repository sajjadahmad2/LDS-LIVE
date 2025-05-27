<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\CustomPasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\PasswordReset;
use Carbon\Carbon;
class ForgotPasswordsController extends Controller
{

    public function submitForgetPasswordForm(Request $request)

    {

        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $token = Str::random(60);

        PasswordReset::updateOrCreate(
            ['email' => $email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        Mail::to($email)->send(new CustomPasswordResetMail($token, $email));

        return back()->with('status', 'Password reset link sent!');
    }

    /**

     * Write code on Method

     *

     * @return response()

     */

    public function showResetPasswordForm($token) {

       return view('auth.forgetPasswordLink', ['token' => $token]);

    }



    /**

     * Write code on Method

     *

     * @return response()

     */

    public function submitResetPasswordForm(Request $request)

    {

        $request->validate([

            'email' => 'required|email|exists:users',

            'password' => 'required|string|min:6|confirmed',

            'password_confirmation' => 'required'

        ]);



        $updatePassword = DB::table('password_resets')

                            ->where([

                              'email' => $request->email,

                              'token' => $request->token

                            ])

                            ->first();



        if(!$updatePassword){

            return back()->withInput()->with('error', 'Invalid token!');

        }



        $user = User::where('email', $request->email)

                    ->update(['password' => Hash::make($request->password)]);



        DB::table('password_resets')->where(['email'=> $request->email])->delete();



        return redirect('/login')->with('message', 'Your password has been changed!');

    }
}
