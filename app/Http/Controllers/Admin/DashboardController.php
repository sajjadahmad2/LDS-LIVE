<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentUser;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\SaveJobLog;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    public function index()
    {
        if (is_role() == 'admin') {
            $agents    = $this->getAgentsByUser(login_id());
            // $campaigns = $this->getCampaignsByUser(login_id());

            // $data = $this->getAgentStatsQuery(login_id())->get();

        } elseif (is_role() == 'superadmin') {
            $agents    = Agent::all();
            // $campaigns = Campaign::all();
            // $data      = $this->getAgentStatsQuery(null)->get(); // Get all agents

        } else {
            $locationIds = AgentUser::where('user_id', login_id())->pluck('location_id')->toArray();
            $agents      = Agent::whereIn('destination_location', $locationIds)->get();
            // $campaigns   = Campaign::with(['agents'])->whereIn('id', $agents->pluck('id'))->get();
            // $data        = $this->getAgentStatsQuery(login_id())->get();
        }

        return view('admin.dashboard', get_defined_vars());
    }
    public function detailDashboard()
    {
        if (is_role() == 'admin') {
            $agents    = $this->getAgentsByUser(login_id());
            $campaigns = $this->getCampaignsByUser(login_id());

            $data = $this->getAgentStatsQuery(login_id())->get();
            return view('admin.detailDashboard', get_defined_vars());

        }
    }
    public function getDashboardStats(Request $request)
    {
        $agentId        = $request->input('agent_id'); // This is agent_id from dropdown
        $loggedInUserId = login_id();                  // Get the user_id for the admin (or location)
        $startDate      = $request->input('start_date');
        $endDate        = $request->input('end_date');

        $query = DB::table('contacts')->where('user_id', $loggedInUserId);

        if (! empty($agentId)) {
            $query->where('agent_id', $agentId);
        }

        $today        = now()->startOfDay();
        $yesterday    = now()->subDay()->startOfDay();
        $startOfMonth = now()->startOfMonth();
        $last7Days    = now()->subDays(6)->startOfDay();

        // Fetch daily & monthly limit only if agent is selected
        $dailyLimit   = null;
        $monthlyLimit = null;

        if (! empty($agentId)) {
            $agent        = DB::table('agents')->where('id', $agentId)->first();
            $dailyLimit   = $agent->daily_limit ?? null;
            $monthlyLimit = $agent->monthly_limit ?? null;
        }

        $stats = [
            'today'             => (clone $query)->whereBetween('created_at', [$today, now()])->count(),
            'yesterday'         => (clone $query)->whereBetween('created_at', [$yesterday, $today])->count(),
            'last7days'         => (clone $query)->whereBetween('created_at', [$last7Days, now()])->count(),
            'thisMonth'         => (clone $query)->whereBetween('created_at', [$startOfMonth, now()])->count(),
            'total'             => (clone $query)->count(),
            'dashboardFiltered' => null,
            'dailyLimit'        => $dailyLimit,
            'monthlyLimit'      => $monthlyLimit,
        ];

        // Only populate dashboardFiltered if date range is provided
        if ($startDate && $endDate) {
            $stats['dashboardFiltered'] = (clone $query)->whereBetween('created_at', [$startDate, $endDate])->count();
        }

        $html = view('admin.dashboard_stats', compact('stats'))->render();

        return response()->json(['html' => $html]);
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
    public function searchLocationsByAjax(Request $request)
    {

        $term = $request->q ?? ($request->term ?? ($request->search ?? ''));
        if (is_role() == 'superadmin') {
            $data = User::query()->select('name as text', 'id')->where('role', 1)->where('status', 1);
        }
        if (! empty($term)) {
            $data->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
            });
        }
        $results = $data->take(100)->get();
        return response()->json($results);
    }
}
