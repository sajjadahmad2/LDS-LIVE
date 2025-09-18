<?php
namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentUser;
use App\Models\Campaign;
use App\Models\CampaignAgent;
use App\Models\LeadType;
use Auth;
use DataTables;
use DB;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $user = Auth::User();
            if ($user->role == 1) {
                $data = Campaign::with(['agents', 'compaignAgents'])->where('user_id', login_id())->get();
                //dd($data);
            } else {
                $locationIds = AgentUser::where('user_id', login_id())->pluck('location_id')->toArray();
                //dd($locationIds);
                $agentIds = Agent::whereIn('destination_location', $locationIds)->pluck('id')->toArray();
                //dd($agentIds);
                $campaignAgent = CampaignAgent::whereIn('agent_id', $agentIds)->pluck('campaign_id')->toArray();
                $data          = Campaign::with('agents')->whereIn('id', $campaignAgent);
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('lead_type', function ($row) {
                    return $row->leadType?->name ?? '';
                })
                ->addColumn('action', function ($row) {
                    $campaignName = addslashes($row->campaign_name);

                    $agents    = json_encode($row->agents->pluck('id')->toArray());
                    $weightage = json_encode(array_map('intval', $row->compaignAgents->pluck('weightage')->toArray()));
                    $priority  = json_encode($row->compaignAgents->pluck('priority')->toArray());
                    if (is_role() == 'superadmin' || is_role() == 'admin') {
                        $btn = '<button type="button" class="btn btn-success me-2" onclick="copyCampaignUrl(' . $row->id . ')">CopyUrl</button>';
                        $btn .= '<button type="button" class="btn btn-primary me-2"
                                    onclick="savaCampaignData(' . $row->id . ', \'' . addslashes($row->lead_type) . '\', \'' . addslashes($campaignName) . '\', \'' . addslashes($agents) . '\', \'' . addslashes($priority) . '\', \'' . addslashes($weightage) . '\')">
                                    Edit
                                </button>';

                        $btn .= '<a href="javascript:void(0);" onclick="deleteCampaign(' . $row->id . ')"><button type="button" class="btn btn-danger">Delete</button></a>';
                    } else {
                        $btn = 'Not Authorized';
                    }
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);

        }
        $agents    = Agent::where('user_id', login_id())->get();
        $campaigns = Campaign::where('user_id', login_id())->get();
        $leadtypes = LeadType::where('user_id', login_id())->get();
        return view('admin.campaigns.index', get_defined_vars());
    }
    public function store(Request $request)
    {

        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'lead_type_id'  => 'required',
            'agents'        => 'required|array',
            'weightage'     => 'required|array',
            'priority'      => 'required|array',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $campaign = Campaign::create([
                    'campaign_name' => $validated['campaign_name'],
                    'user_id'       => auth()->id(),
                    'lead_type'     => $validated['lead_type_id'],
                ]);

                foreach ($validated['agents'] as $rowId => $agentId) {
                    // Create campaign agent row
                    $campaignAgent = CampaignAgent::create([
                        'campaign_id' => $campaign->id,
                        'agent_id'    => $agentId,
                        'weightage'   => $validated['weightage'][$rowId] ?? 0,
                        'priority'    => $validated['priority'][$rowId] ?? null,
                        'user_id'     => auth()->id(),
                    ]);

                    // Count total contacts
                    $totalContacts = DB::table('contacts')
                        ->where('agent_id', $agentId)
                        ->where('campaign_id', $campaign->id)
                        ->count();

                    // Count today's contacts
                    $dailyContacts = DB::table('contacts')
                        ->where('agent_id', $agentId)
                        ->where('campaign_id', $campaign->id)
                        ->whereDate('created_at', now()->toDateString())
                        ->count();

                    // Count this month's contacts
                    $monthlyContacts = DB::table('contacts')
                        ->where('agent_id', $agentId)
                        ->where('campaign_id', $campaign->id)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->count();

                    // Update the campaign_agent record
                    $campaignAgent->update([
                        'sent_contacts' => $totalContacts,
                        'daily_sent'    => $dailyContacts,
                        'monthly_sent'  => $monthlyContacts,
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Campaign created successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create campaign! ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'lead_type_id'  => 'required',
            'agents'        => 'required|array',
            'weightage'     => 'required|array', // ✅ lowercase
            'priority'      => 'required|array',
        ]);

        try {
            DB::transaction(function () use ($campaign, $validated) {
                $campaign->update([
                    'campaign_name' => $validated['campaign_name'],
                    'user_id'       => auth()->id(),
                    'lead_type'     => $validated['lead_type_id'],
                ]);

                CampaignAgent::where('campaign_id', $campaign->id)->delete();

                foreach ($validated['agents'] as $rowId => $agentId) {
                    $campaignAgent = CampaignAgent::create([
                        'campaign_id' => $campaign->id,
                        'agent_id'    => $agentId,
                        'weightage'   => $validated['weightage'][$rowId] ?? 0,
                        'priority'    => $validated['priority'][$rowId] ?? null,
                        'user_id'     => auth()->id(),
                    ]);
                    // Count total contacts
                    $totalContacts = DB::table('contacts')
                        ->where('agent_id', $agentId)
                        ->where('campaign_id', $campaign->id)
                        ->count();

                    // Count today's contacts
                    $dailyContacts = DB::table('contacts')
                        ->where('agent_id', $agentId)
                        ->where('campaign_id', $campaign->id)
                        ->whereDate('created_at', now()->toDateString())
                        ->count();

                    // Count this month's contacts
                    $monthlyContacts = DB::table('contacts')
                        ->where('agent_id', $agentId)
                        ->where('campaign_id', $campaign->id)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->count();

                    // Update the campaign_agent record
                    $campaignAgent->update([
                        'sent_contacts' => $totalContacts,
                        'daily_sent'    => $dailyContacts,
                        'monthly_sent'  => $monthlyContacts,
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Campaign updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update campaign! ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        CampaignAgent::where('campaign_id', $campaign->id)->delete();
        $campaign->delete();
        return response()->json(['success' => 'Campaign deleted successfully!']);
    }

    public function getAgentWeightage($agentId, $campaignId = null)
    {
        $agent = CampaignAgent::where('agent_id', $agentId)->where('campaign_id', $campaignId)->first(); // Fetch the first matching record
        if ($agent) {
            $weightage = $agent->weightage;
            return response()->json([
                'success' => true,
                'data'    => [
                    'weightage' => $weightage,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Agent not found'], 404);
    }
    public function campaignShow()
    {
        $campaigns = Campaign::where('user_id', login_id())->get();
        return response()->json($campaigns);

    }
    public function searchCampaignByAjax(Request $request)
    {
        $term = $request->q ?? ($request->term ?? ($request->search ?? ''));
        $data = Campaign::query()->select('campaign_name as text', 'id')->where('user_id', login_id());
        if (! empty($term)) {
            $data->where(function ($query) use ($term) {
                $query->where('campaign_name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
            });
        }
        $results = $data->take(100)->get();
        return response()->json($results);
    }
}
