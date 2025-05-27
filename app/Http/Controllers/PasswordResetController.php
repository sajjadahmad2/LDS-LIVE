<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Mail\Message;

class PasswordResetController extends Controller
{
    public function send_reset_password_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // Check if the user's email exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'We could not find a user with that email address.']);
        }

        // Generate a unique token
        $token = Str::random(60);

        // Save data to PasswordReset table
        PasswordReset::updateOrCreate(
            ['email' => $email], // Unique key
            [
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        // Send the password reset email
        Mail::send('reset', ['token' => $token], function (Message $message) use ($email) {
            $message->subject('Reset Your Password');
            $message->to($email);
        });

        return back()->with('status', 'We have emailed your password reset link!');
    }

    public function reset(Request $request, $token)
    {
        // Delete tokens older than 2 minutes
        PasswordReset::where('created_at', '<=', Carbon::now()->subMinutes(2))->delete();

        // Validate the new password
        $request->validate([
            'password' => 'required|confirmed',
        ]);

        // Check if the reset token exists and is valid
        $passwordReset = PasswordReset::where('token', $token)->first();
        if (!$passwordReset) {
            return response([
                'message' => 'Token is invalid or has expired',
                'status' => 'failed'
            ], 404);
        }

        // Retrieve the user and reset the password
        $user = User::where('email', $passwordReset->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token after a successful reset
        $passwordReset->delete();

        return response([
            'message' => 'Password reset successful',
            'status' => 'success'
        ], 200);
    }
}
