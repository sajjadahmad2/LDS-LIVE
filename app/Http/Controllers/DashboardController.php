<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Campaign;
class AdminAuthController extends Controller
{
    public function index(){
        $agents = Agent::where('user_id', login_id())->get();
        $campaign =Campaign::where('user_id', login_id())->get();
        return view('admin.dashboard', get_defined_vars());
    }
}
