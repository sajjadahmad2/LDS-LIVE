<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AutoAuthController extends Controller
{
    protected const VIEW = 'autoauth';
    public function authChecking(Request $req)
    {
        \Log::info('User data:', ['user' => $req->all()]);
        if ($req->ajax()) {
            \Log::info('User data:');
            //dd($req->all());
            if ($req->has('location') && $req->has('token')) {
                $location = $req->location;
                $user = User::with('ghlauth')->where('location_id', $req->location)->first();
                if (!$user) {
                    $user = new User();
                    $user->name = 'Location';
                    $user->last_name= 'User';
                    $user->email = $location . '@presave.net';
                    $user->password = bcrypt('presave_' . $location);
                    $user->role =1;
                    $user->added_by = 1;
                    $user->location_id = $location;
                    $user->ghl_api_key = '-';
                    $user->save();
                }
                $user->ghl_api_key = $req->token;
                $user->save();
                request()->merge(['user_id' => $user->id]);
                session([
                    'location_id' => $user->location_id,
                    'uid' => $user->id,
                    'user_id' => $user->id,
                    'user_loc' => $user->location_id,
                ]);


                $res = new \stdClass;
                $res->user_id = $user->id;
                $res->location_id = $user->location_id ?? null;
                $res->is_crm = false;
                request()->user_id = $user->id;
                $res->token = $user->ghl_api_key;
                $token = $user->crmauth;
                $res->crm_connected = false;
                if ($token) {
                    // request()->code = $token;
                    list($tokenx, $token) = \CRM::go_and_get_token($token->refresh_token, 'refresh', $user->id,$token);
                    $res->crm_connected = $tokenx && $token;
                }
                if (!$res->crm_connected) {
                    $res->crm_connected = \CRM::ConnectOauth($req->location, $res->token, false, $user->id);
                }
                if($res->crm_connected){
                    if (\Auth::check()) {
                        \Auth::logout();
                        sleep(1);
                        //return response()->json(['logout user']);
                    }
                    \Auth::login($user);
                }

                $res->is_crm = $res->crm_connected;
                $res->token_id = encrypt($res->user_id);
                return response()->json($res);
            }
        }
        return response()->json(['status' => 'invalid request']);
    }

    public function connect()
    {
        return view(self::VIEW . '.connect');
    }

    public function authError()
    {
        return view(self::VIEW . '.error');
    }
}
