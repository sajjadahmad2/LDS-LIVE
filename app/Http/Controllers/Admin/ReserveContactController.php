<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReserveContact;
use App\Models\Agent;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Contact;
use DB;
use App\Models\Campaign;
use App\Services\ProccessContactServices;
use App\Models\Log;
class ReserveContactController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = ReserveContact::select('id', 'phone', 'email', 'created_at', 'first_name','contact_id', 'state')
                    ->where('status', 'Not Sent')
                    ->orderBy('id', 'desc');
                return DataTables::of($data)
                    ->addIndexColumn()

                    ->addColumn('action', function ($row) {
                        $btn = '<div class="row row-cols-auto g-3">';
                        // Send button
                        $btn .= '<div class="col">';
                        $btn .= '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-placement="top" title="Edit User" data-bs-target="#userModal" onclick="savaData(\'' . $row->id . '\', \'' . $row->first_name . '\', \'' . $row->email . '\', \'' . $row->state . '\')">Send</button>';
                        $btn .= '</div>';
                        $btn .= '</div>';

                        return $btn; // Return the button HTML
                    })
                    ->rawColumns(['state', 'action']) // Ensure the 'state' and 'action' columns are treated as raw HTML
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }
        }

        return view('admin.reserve.index');
    }
    public function log(Request $request) {
        if ($request->ajax()) {
            try {
                $data = Log::select(['id', 'contact_id', 'name', 'email', 'state', 'reason', 'message'])->orderBy('id', 'desc');
                return DataTables::of($data)->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }
        }

        return view('admin.log');
    }

    public function fetchState($state=null){
        //dd($state);
        $agents = Agent::whereHas('states', function ($query) use ($state) {
            $query->where(DB::raw('TRIM(LOWER(state))'), strtolower(trim($state)))
                ->orWhere(DB::raw('TRIM(LOWER(short_form))'), strtolower(trim($state)));
        })->pluck('name', 'id'); // Fetch only `id` and `name`
       //dd($agents);
        return response()->json($agents);
    }

    public function assignAgent(Request $request)
    {
        $agentId = $request->agent_id;
        $leadId = $request->lead_id;

        // Fetch the agent
        $agent = Agent::where('id', $agentId)->first();
        if (!$agent) {
            return response()->json(['message' => 'Agent not found!'], 404);
        }

        // Fetch agent's assigned campaign
        $campaign = Campaign::whereHas('agents', function ($query) use ($agent) {
            $query->where(DB::raw('TRIM(LOWER(name))'), strtolower(trim($agent->name)));
        })->first();

        // Get current date and month in America/Chicago timezone
        $currentDate = Carbon::now('America/Chicago')->format('Y-m-d');
        $currentMonth = Carbon::now('America/Chicago')->month;

        // Count agent's assigned contacts
        $dailyCount = Contact::where('agent_id', $agentId)
            ->whereDate('created_at', $currentDate)
            ->count();

        $monthlyCount = Contact::where('agent_id', $agentId)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        $totalCount = Contact::where('agent_id', $agentId)->count();

        // Check if agent's limits are exceeded
        if ($dailyCount >= $agent->daily_limit) {
            return response()->json(['message' => 'Daily limit reached for this agent.'], 403);
        }

        if ($monthlyCount >= $agent->monthly_limit) {
            return response()->json(['message' => 'Monthly limit reached for this agent.'], 403);
        }

        if ($totalCount >= $agent->total_limit) {
            return response()->json(['message' => 'Total contact limit reached for this agent.'], 403);
        }

        // Fetch and decode contact data
        $reserveContact = ReserveContact::select('contact_json')->find($leadId);
        if (!$reserveContact) {
            return response()->json(['message' => 'Lead not found!'], 404);
        }

        $reserveContact = json_decode(base64_decode($reserveContact->contact_json), true);

        // Process contact
        $processContact = new ProccessContactServices();
        $processContact->handleProccessContact($reserveContact, $agent, $campaign);

        return response()->json(['message' => 'Agent assigned successfully!']);
    }
}
