<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CRMController extends Controller
{

    public function crmCallback(Request $request)
    {
        $code = $request->code ?? null;
        if ($code) {
            $user_id = null;
            if (auth()->check()) {
                $user = loginUser(); //auth user
                // if ($user->role == company_role()) {

                // }
                $user_id = $user->id;

            }
            $code = \CRM::crm_token($code, '');
            $code = json_decode($code);
            $user_type = $code->userType ?? null;
            $main = route('admin.dashboard'); //change with any desired
            if ($user_type) {

                $token = $user->crmauth ?? null;
                list($connected, $con) = \CRM::go_and_get_token($code, '', $user_id, $token);
                if ($connected) {
                    
                    return redirect($main)->with('success', 'Connected Successfully');
                }
                return redirect($main)->with('error', json_encode($code));

            }
            return response()->json(['message' => 'Not allowed to connect']);
        }
    }
}
