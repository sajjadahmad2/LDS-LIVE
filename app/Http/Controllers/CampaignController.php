<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Agent;
use App\Models\CampaignCarrierType;
use App\Models\CampaignAgent;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use App\Models\AgentUser;
use DB;
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
                $data = Campaign::with('agents')->whereIn('id', $campaignAgent);
            }
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('agents', function ($row) {
                return $row->agents->pluck('name')->join(', ');
            })
            ->addColumn('weightage', function ($row) { // ✅ Use campaign_agents weightage
                return $row->compaignAgents->pluck('weightage')->join(', ');
            })
            ->addColumn('priority', function ($row) { // ✅ Add priority from campaign_agents
                return $row->compaignAgents->pluck('priority')->join(', ');
            })
            ->addColumn('action', function ($row) {
                $campaignName = addslashes($row->campaign_name);
                $agents = json_encode($row->agents->pluck('id')->toArray());
                $weightage = json_encode(array_map('intval', $row->compaignAgents->pluck('weightage')->toArray()));
                $priority = json_encode($row->compaignAgents->pluck('priority')->toArray());
                if (is_role() == 'superadmin' || is_role() == 'admin') {
                    $btn = '<button type="button" class="btn btn-primary me-2" onclick="savaCampaignData(' . $row->id . ', \'' . addslashes($campaignName) . '\', \'' . addslashes($agents) . '\', \'' . addslashes($priority) . '\', \'' . addslashes($weightage) . '\')">Edit</button>';
                    $btn .= '<a href="javascript:void(0);" onclick="deleteCampaign(' . $row->id . ')"><button type="button" class="btn btn-danger">Delete</button></a>';
                } else {
                    $btn = 'Not Authorized';
                }
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);

        }
        $agents = Agent::where('user_id', login_id())->get();
        $campaigns = Campaign::where('user_id', login_id())->get();

        return view('admin.campaigns.index', get_defined_vars());
    }

    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'agents' => 'nullable|array',
            'Weightage' => 'required|array', // Ensure it's an array
            'priority' => 'required|array',  // Ensure it's an array
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Create a new campaign
                $campaign = Campaign::create([
                    'campaign_name' => $validated['campaign_name'],
                    'user_id' => auth()->id(),
                ]);

                // Attach agents with weightage & priority
                if (!empty($validated['agents'])) {
                    foreach ($validated['agents'] as $agentId) {
                        CampaignAgent::create([
                            'campaign_id' => $campaign->id,
                            'agent_id' => $agentId,
                            'weightage' => $validated['Weightage'][$agentId] ?? 0,  // Default to 0 if missing
                            'priority' => $validated['priority'][$agentId] ?? null, // Default to NULL if missing
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            });

            return response()->json([ 'success' =>true, 'message' => 'Campaign created successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create campaign! ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        // Validate the incoming request
        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'agents' => 'nullable|array',   // Agents should be an array
            'Weightage' => 'nullable|array', // Weightage should be an array
            'priority' => 'nullable|array',  // Priority should also be an array
        ]);

        // Update the campaign name and user_id
        $campaign->update([
            'campaign_name' => $validated['campaign_name'],
            'user_id' => auth()->id(),
        ]);

        // Remove all existing campaign-agent associations
        CampaignAgent::where('campaign_id', $campaign->id)->delete();

        // Loop through each agent and insert/update data in the pivot table
        if (isset($validated['agents']) && count($validated['agents']) > 0) {
            foreach ($validated['agents'] as $agentId) {
                // Use $validated['Weightage'][$agentId] and $validated['priority'][$agentId] for weightage and priority respectively
                CampaignAgent::create([
                    'campaign_id' => $campaign->id,
                    'agent_id' => $agentId,
                    'weightage' => $validated['Weightage'][$agentId] ?? 0,  // Default to 0 if weightage is missing
                    'priority' => $validated['priority'][$agentId] ?? null, // Default to NULL if priority is missing
                    'user_id' => auth()->id(),
                ]);
            }
        }

        // Return a success response
        return response()->json([ 'success' =>true, 'message' => 'Campaign created successfully!']);
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
                'data' => [
                    'weightage' => $weightage,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Agent not found'], 404);
    }
    public function campaignShow(){
    $campaigns = Campaign::where('user_id', login_id())->get();
    return response()->json($campaigns);

    }
    public function searchCampaignByAjax(Request $request){
        $term = $request->q ?? ($request->term ?? ($request->search ?? ''));
        $data = Campaign::query()->select('campaign_name as text', 'id')->where('user_id',login_id());
        if (!empty($term)) {
            $data->where(function ($query) use ($term) {
                $query->where('campaign_name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
            });
        }
        $results = $data->take(100)->get();
        return response()->json($results);
    }
}
