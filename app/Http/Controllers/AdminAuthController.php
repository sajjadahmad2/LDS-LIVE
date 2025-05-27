<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\AgentUser;
class AdminAuthController extends Controller
{
    public function index(){

        if(is_role() == 'admin' ){
            $agents = Agent::where('user_id', login_id())->get();
            $campaign =Campaign::where('user_id', login_id())->get();
        }elseif(is_role() == 'superadmin'){
            $agents = Agent::all();
            $campaign =Campaign::all();
        }else{
            $locationIds = AgentUser::where('user_id', login_id())->pluck('location_id')->toArray();
            //dd($locationIds);
            $agents = Agent::whereIn('destination_location', $locationIds)->get();
            $campaign = Campaign::with(['agents'])->whereIn('id', $agents->pluck('id'))->get();
        }
        return view('admin.dashboard', get_defined_vars());
    }
}
