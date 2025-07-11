<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentUser;
use App\Models\Campaign;
use App\Models\CampaignAgent;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\SaveJobLog;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $nowChicago = Carbon::now('America/Chicago');
        $today      = $nowChicago->toDateString();
        $type       = $request->query('type');
        $dateRange  = $request->query('dateRange');
        $agentId    = $request->query('agentId');
        $campaignId = $request->query('campaignId');

        $startDate = $endDate = null;
        if ($dateRange) {
            $dates     = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('m/d/Y', $dates[0], 'America/Chicago')->startOfDay();
            $endDate   = Carbon::createFromFormat('m/d/Y', $dates[1], 'America/Chicago')->endOfDay();
        }

        // No filters = default dashboard view
        if (! $type && ! $dateRange && ($agentId === 'all' || ! $agentId) && ($campaignId === 'all' || ! $campaignId)) {
            $loginId = login_id();

            if (is_role() == 'admin') {
                $agents    = $this->getAgentsByUser($loginId);
                $campaigns = $this->getCampaignsByUser($loginId);
                $data      = $this->getAgentStatsQuery($loginId)->get();
            } elseif (is_role() == 'superadmin') {
                $agents    = Agent::all();
                $campaigns = Campaign::all();
                $data      = $this->getAgentStatsQuery(null)->get();
            } else {
                $locationIds = AgentUser::where('user_id', $loginId)->pluck('location_id');
                $agents      = Agent::whereIn('destination_location', $locationIds)->get();
                $campaigns   = Campaign::with(['agents'])->whereIn('id', $agents->pluck('id'))->get();
                $data        = $this->getAgentStatsQuery($loginId)->get();
            }

            return view('admin.dashboard', compact('agents', 'campaigns', 'data'));
        }

        // Handle filter mode: agent/campaign
        if (in_array($type, ['agent', 'campaign'])) {
            if (! is_null($campaignId) && ($agentId === null || $agentId === 'all')) {
                $agentId = CampaignAgent::where('campaign_id', $campaignId)->pluck('agent_id')->toArray();
            } else {
                $agentId = is_array($agentId) ? $agentId : [$agentId];
            }

            // Subqueries for counts
            $dailyContacts = Contact::selectRaw('agent_id, COUNT(*) as daily_contacts_count')
                ->whereDate('created_at', $today)
                ->groupBy('agent_id');

            $monthlyContacts = Contact::selectRaw('agent_id, COUNT(*) as monthly_contacts_count')
                ->whereMonth('created_at', $nowChicago->month)
                ->whereYear('created_at', $nowChicago->year)
                ->groupBy('agent_id');

            $totalContacts = Contact::selectRaw('agent_id, COUNT(*) as total_contacts_count')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->groupBy('agent_id');

            $query = Agent::select(
                'agents.id',
                'agents.name',
                'agents.priority',
                'agents.daily_limit',
                'agents.monthly_limit',
                'agents.total_limit',
                DB::raw('COALESCE(daily_contacts.daily_contacts_count, 0) as daily_contacts_count'),
                DB::raw('COALESCE(monthly_contacts.monthly_contacts_count, 0) as monthly_contacts_count'),
                DB::raw('COALESCE(total_contacts.total_contacts_count, 0) as total_contacts_count')
            )
                ->leftJoinSub($dailyContacts, 'daily_contacts', 'agents.id', '=', 'daily_contacts.agent_id')
                ->leftJoinSub($monthlyContacts, 'monthly_contacts', 'agents.id', '=', 'monthly_contacts.agent_id')
                ->leftJoinSub($totalContacts, 'total_contacts', 'agents.id', '=', 'total_contacts.agent_id')
                ->when($agentId !== null && $agentId !== ['all'], function ($q) use ($agentId) {
                    $q->whereIn('agents.id', $agentId);
                });

            $data = $query
                ->orderBy('agents.priority', 'asc')
                ->orderByRaw('(agents.monthly_limit - monthly_contacts.monthly_contacts_count) desc')
                ->orderByRaw('(agents.daily_limit - daily_contacts.daily_contacts_count) desc')
                ->orderByRaw('(agents.total_limit - total_contacts.total_contacts_count) desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => "$type data fetched successfully.",
                'data'    => $data,
            ]);
        }

        return view('admin.dashboard', [
            'agents'    => [],
            'campaigns' => [],
            'data'      => [],
        ]);
    }

    public function agentCompaignSearch(Request $request)
    {
        // Retrieve parameters from the query string
        $type       = $request->query('type');
        $dateRange  = $request->query('dateRange');
        $agentId    = $request->query('agentId');
        $campaignId = $request->query('campaignId');
        // Handle date range if provided
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
        if ($dateRange) {
            $dates     = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('m/d/Y', $dates[0], 'America/Chicago')->startOfDay()->format('Y-m-d H:i:s');
            $endDate   = Carbon::createFromFormat('m/d/Y', $dates[1], 'America/Chicago')->endOfDay()->format('Y-m-d H:i:s');
        } else {
            // Default to current month range up to now
            $startDate = Carbon::now('America/Chicago')
                ->startOfMonth()
                ->format('Y-m-d H:i:s');

            $endDate = Carbon::now('America/Chicago')
                ->endOfDay() // Or ->now() if you want exact current time
                ->format('Y-m-d H:i:s');
        }
        $data = null;

        if ($type === "agent" || $type === 'campaign') {
            if (($campaignId == 'all' && $agentId == 'all') || ($campaignId == null && $agentId == null)) {

                return response()->json([
                    'success'  => true,
                    'message'  => "Get All data successfully.",
                    'data'     => $data,
                    'redirect' => route('admin.dashboard'),
                ], 200);
                return redirect()->route('admin.dashboard');
            }
            if (! is_null($campaignId) && (is_null($agentId) || $agentId == 'all')) {
                $agentId = CampaignAgent::where('campaign_id', $campaignId)->pluck('agent_id')->toArray();

            } else {
                $aid       = $agentId;
                $agentId   = [];
                $agentId[] = $aid;
            }

            $query = Agent::select(
                'agents.id',
                'agents.priority',
                'agents.daily_limit',
                'agents.monthly_limit',
                'agents.total_limit',
                'agents.name',
                DB::raw('(SELECT COUNT(*) FROM contacts WHERE contacts.agent_id = agents.id AND MONTH(contacts.created_at) = MONTH(CURRENT_DATE)) as monthly_contacts_count'),
                DB::raw('(SELECT COUNT(*) FROM contacts WHERE contacts.agent_id = agents.id AND DATE(contacts.created_at) = CURRENT_DATE) as daily_contacts_count'),
                DB::raw('(SELECT COUNT(*) FROM contacts WHERE contacts.agent_id = agents.id' . (
                    ! empty($startDate) && ! empty($endDate)
                    ? ' AND contacts.created_at  BETWEEN "' . $startDate . '" AND "' . $endDate . '"'
                    : ''
                ) . ') as total_contacts_count')
            )
                ->leftJoin('contacts', 'agents.id', '=', 'contacts.agent_id')
                ->groupBy(
                    'agents.id',
                    'agents.priority',
                    'agents.daily_limit',
                    'agents.monthly_limit',
                    'agents.total_limit',
                    'agents.name'
                )
                ->whereIn("agents.id", $agentId);
            if (! empty($startDate) && ! empty($endDate)) {
                $query->whereBetween('agents.created_at', [$startDate, $endDate]);
            }
            $data = $query
                ->orderBy('agents.priority', 'asc')
                ->orderByRaw('(agents.monthly_limit - monthly_contacts_count) desc')
                ->orderByRaw('(agents.daily_limit - daily_contacts_count) desc')
                ->orderByRaw('(agents.total_limit - total_contacts_count) desc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'message' => "$type data fetched successfully.",
            'data'    => $data,
        ], 200);

        if (empty($data) || ($type !== 'all' && $data->isEmpty())) {
            return response()->json([
                'success' => false,
                'message' => "No $type data found.",
            ], 404);
        }
    }
    /**
     * Get agents filtered by user ID
     */
    private function getAgentsByUser($userId)
    {
        return Agent::where('user_id', $userId)->get();
    }

    /**
     * Get campaigns filtered by user ID
     */
    private function getCampaignsByUser($userId)
    {
        return Campaign::where('user_id', $userId)->get();
    }

    /**
     * Get agent statistics query
     */
    private function getAgentStatsQuery($userId = null)
    {
        $query = Agent::select(
            'agents.id',
            'agents.priority',
            'agents.daily_limit',
            'agents.monthly_limit',
            'agents.total_limit',
            'agents.name',
            DB::raw('(SELECT COUNT(*) FROM contacts WHERE contacts.agent_id = agents.id AND MONTH(contacts.created_at) = MONTH(CURRENT_DATE)) as monthly_contacts_count'),
            DB::raw('(SELECT COUNT(*) FROM contacts WHERE contacts.agent_id = agents.id AND DATE(contacts.created_at) = CURRENT_DATE) as daily_contacts_count'),
            DB::raw('(SELECT COUNT(*) FROM contacts WHERE contacts.agent_id = agents.id) as total_contacts_count')
        )
            ->leftJoin('contacts', 'agents.id', '=', 'contacts.agent_id')
            ->groupBy(
                'agents.id',
                'agents.priority',
                'agents.daily_limit',
                'agents.monthly_limit',
                'agents.total_limit',
                'agents.name'
            )
            ->orderBy('agents.priority', 'asc')
            ->orderByRaw('(agents.monthly_limit - monthly_contacts_count) desc')
            ->orderByRaw('(agents.daily_limit - daily_contacts_count) desc')
            ->orderByRaw('(agents.total_limit - total_contacts_count) desc');
        if ($userId) {
            $query->where('agents.user_id', $userId);
        }

        return $query;
    }
    public function getCustomField(Request $request)
    {
        ini_set('memory_limit', '-1');
        if ($request->ajax()) {
            try {
                $data = CustomField::get();
                // return response()->json([
                //     'error' =>  json_decode(json_encode($data), true)
                // ], 500);
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage(),
                ], 500);
            }
        }

        return view('admin.customField.index');
    }
    public function changeAgent(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|integer',
            'agent_id'   => 'required|integer',
        ]);

        $contact = Contact::find($request->contact_id);

        if (! $contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        $contact->agent_id = $request->agent_id;
        $contact->save();

        return response()->json(['message' => 'Agent changed successfully']);
    }
    public function getJobLogs(Request $request)
    {
        ini_set('memory_limit', '-1');
        if ($request->ajax()) {
            try {
                $data = SaveJobLog::get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->editColumn('message', function ($row) {
                        return formatLog($row->message);
                    })
                    ->rawColumns(['message'])
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage(),
                ], 500);
            }
        }

        return view('admin.saveJobLog.index');
    }
}
