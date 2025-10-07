<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\State;
use App\Models\Log;
use App\Models\ReserveContact;
use App\Services\ProccessContactServices;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReserveContactController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
                $query = ReserveContact::select(
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'city',
                    'postal_code',
                    'state',
                    'lead_type',
                    'reason',
                    'created_at',
                    'campaign_id'
                )
                    ->with(['campaign:id,campaign_name,lead_type'])
                    ->where('status', 'Not Sent');

                // Filter: State
                if ($request->filled('state_ids') && $request->state_ids !== 'all') {
                    $states = State::where('id', $request->state_ids)->pluck('state');
                    $query->whereIn('state', $states);
                }

                // Filter: Campaign
                if ($request->filled('campaign_ids') && $request->campaign_ids !== 'all') {
                    $query->whereIn('campaign_id', [$request->campaign_ids]);
                }

                // Filter: Date Range
                if ($request->filled('customDateRange') && $request->customDateRange !== 'all') {
                    $dates = explode(' to ', $request->customDateRange);
                    if (count($dates) === 2) {
                        $start = Carbon::createFromFormat('Y-m-d', trim($dates[0]), 'America/Chicago')->startOfDay();
                        $end   = Carbon::createFromFormat('Y-m-d', trim($dates[1]), 'America/Chicago')->endOfDay();
                        $query->whereBetween('created_at', [$start, $end]);
                    }
                }

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->editColumn('first_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                    ->editColumn('state', fn($row) => $row->state) // Display state as plain text
                    ->addColumn('campaign.campaign_name', function ($row) {
                        return optional($row->campaign)->campaign_name;
                    })
                    ->addColumn('action', function ($row) {
                        return '<button type="button" class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#userModal"
                        onclick="savaData(\'' . $row->id . '\', \'' . $row->first_name . '\', \'' . $row->email . '\', \'' . $row->state . '\' ,\'' . $row->lead_type ?? optional($row->campaign)->lead_type .'\')">Send</button>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage(),
                ], 500);
            }
        }

        return view('admin.reserve.index');
    }
    public function log(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = Log::select(['id', 'contact_id', 'name', 'email', 'state', 'reason'])
                    ->orderBy('id', 'desc');

                return DataTables::of($data)
                    ->addColumn('contact_source', function ($row) {
                        $decodedMessage = json_decode($row->message, true);
                        return $decodedMessage['contact_source'] ?? 'N/A';
                    })
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage(),
                ], 500);
            }
        }

        return view('admin.log');
    }
            public function fetchState($state = null, $leadTypeId = null)
            {
                $agents = Agent::whereHas('states', function ($query) use ($state) {
                    $query->whereHas('state', function ($q) use ($state) {
                        $q->where(DB::raw('TRIM(LOWER(state))'), strtolower(trim($state)))
                        ->orWhere(DB::raw('TRIM(LOWER(short_form))'), strtolower(trim($state)));
                    });
                })
                ->whereHas('agentLeadTypes', function ($query) use ($leadTypeId) {
                    $query->where('lead_type', $leadTypeId);
                })
                ->pluck('name', 'id');

                return response()->json($agents);
            }


    public function assignAgent(Request $request)
    {
        $agentId = $request->agent_id;
        $leadId  = $request->lead_id;

        // Fetch the agent
        $agent = Agent::where('id', $agentId)->first();
        if (! $agent) {
            return response()->json(['message' => 'Agent not found!'], 404);
        }

        // Fetch agent's assigned campaign
        $campaign = Campaign::whereHas('agents', function ($query) use ($agent) {
            $query->where(DB::raw('TRIM(LOWER(name))'), strtolower(trim($agent->name)));
        })->first();

        // Get current date and month in America/Chicago timezone
        $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
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
        if (! $reserveContact) {
            return response()->json(['message' => 'Lead not found!'], 404);
        }

        $reserveContact = json_decode(base64_decode($reserveContact->contact_json), true);

        // Process contact
        $processContact = new ProccessContactServices();
        $processContact->handleProccessContact($reserveContact, $agent, $campaign);

        return response()->json(['message' => 'Agent assigned successfully!']);
    }
}
