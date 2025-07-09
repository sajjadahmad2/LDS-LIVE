<?php
namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentCarrierType;
use App\Models\AgentState;
use App\Models\AgentUser;
use App\Models\CampaignAgent;
use App\Models\CompanyLocation;
use App\Models\CustomField;
use App\Models\GhlAuth;
use App\Models\State;
use App\Models\User;
use App\Services\agentUserData;
use Auth;
use Carbon\Carbon;
use DataTables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    /**
     * Display a listing of the agents.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $user = Auth::User();
            if ($user->role == 1) {
                $data = Agent::with(['states', 'carrierTypes'])->where('user_id', login_id());
            } else {
                $data = Agent::with(['states', 'carrierTypes'])->whereIn('destination_location',
                    AgentUser::where('user_id', login_id())->pluck('location_id'));
            }
            if ($request->has('agent_ids') && $request->agent_ids !== 'all' && $request->agent_ids != '') {
                $data->where('id', $request->agent_ids);
            }
            if ($request->has('state_ids') && $request->state_ids !== 'all' && $request->state_ids != '') {
                $agentState = AgentState::where('state_id', $request->state_ids)->pluck('agent_id')->toArray();
                $data->whereIn('id', $agentState);
            }
            if ($request->has('campaign_ids') && $request->campaign_ids !== 'all' && $request->campaign_ids != '') {
                $agentCampaign = CampaignAgent::where('campaign_id', $request->campaign_ids)->pluck('agent_id')->toArray();
                //\Log::info(["agentCampaign ids",$agentCampaign]);
                $data->whereIn('id', $agentCampaign);
            }
            // \Log::info(["Date Range   gjh"=> $request->customDateRange]);
            // \Log::info(["Date Range   gjh"=> $request->customDateRange]);

            if ($request->has('customDateRange') && $request->customDateRange !== 'all' && $request->customDateRange != '') {
                $dates = explode(' to ', $request->customDateRange);
                if (count($dates) == 2) {
                    $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]), 'America/Chicago')->startOfDay()->format('Y-m-d H:i:s');
                    $endDate = Carbon::createFromFormat('Y-m-d', trim($dates[1]), 'America/Chicago')->endOfDay()->format('Y-m-d H:i:s');
                    //\Log::info(["start and End date" => [$startDate, $endDate]]);
                    $data->whereBetween('created_at', [$startDate, $endDate]);
                }
            }

            // Search logic
            if (!empty($request->search['value'])) {
                $search = $request->search['value'];

                $data->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('destination_location', 'LIKE', "%{$search}%")
                        ->orWhereHas('states', function ($query) use ($search) {
                            $query->where('state', 'LIKE', "%{$search}%");
                        });
                });
            }

            return Datatables::of($data->get()) // ✅ Ensure we fetch data
                ->addIndexColumn()
                ->addColumn('states', function ($row) {
                    return $row->states->pluck('state')->join(', ');
                })
                ->addColumn('carrier_type', function ($row) {
                    return $row->carrierTypes->pluck('carrier_type')->join(', ');
                })
                ->addColumn('action', function ($row) {
                    $emailExists = \App\Models\User::where('agent_id', $row->id)->exists();
                    // \Log::info(["User Email", $emailExists]);

                    $user = \App\Models\User::where('agent_id', $row->id)->first();
                    $location_id = [];
                    $from_agents = 0;

                    if ($user && $user->role == 2) {
                        $location_id = \App\Models\AgentUser::where('user_id', $user->id)->pluck('location_id')->toArray();
                        $from_agents = 1;
                    }

                    // Escape values safely
                    $npm_number = addslashes($row->npm_number ?? '');
                    $cross_link = addslashes($row->cross_link ?? '');
                    $weightage  = addslashes($row->weightage ?? '');

                    $consent = json_encode($row->consent, JSON_HEX_APOS | JSON_HEX_QUOT);
                    $carrierTypes = json_encode($row->carrierTypes->pluck('carrier_type')->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT);
                    $states = json_encode($row->states->pluck('id')->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT);
                    $locations = json_encode($location_id, JSON_HEX_APOS | JSON_HEX_QUOT);

                    if (is_role() == 'superadmin' || is_role() == 'admin') {
                        $btn = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agentModal"
                            onclick="savaAgentData('
                            . $row->id . ', \''
                            . addslashes($row->name) . '\', \''
                            . addslashes($row->email) . '\', \''
                            . addslashes($row->destination_location) . '\', \''
                            . addslashes($row->destination_webhook) . '\', \''
                            . $row->priority . '\', \''
                            . $row->daily_limit . '\', \''
                            . $row->monthly_limit . '\', \''
                            . $row->total_limit . '\', '
                            . htmlspecialchars($consent, ENT_QUOTES, 'UTF-8') . ', '
                            . htmlspecialchars($carrierTypes, ENT_QUOTES, 'UTF-8') . ', '
                            . htmlspecialchars($states, ENT_QUOTES, 'UTF-8') . ', '
                            . htmlspecialchars($locations, ENT_QUOTES, 'UTF-8') . ', \''
                            . $npm_number . '\', \''
                            . $weightage . '\', \''
                            . $cross_link . '\', '
                            . ($from_agents ? 'true' : 'false')
                            . ')">Edit</button>';

                        $btn .= '<a href="javascript:void(0);" onclick="deleteAgent(' . $row->id . ')">
                                    <button type="button" class="btn btn-primary mx-2">Delete</button>
                                 </a>';
                        $btn .= '<a href="javascript:void(0);" class="status_changes" data-status="' . $row->id . '">
                                    <button type="button" class="btn btn-primary mx-2">Status</button>
                                 </a>';
                    } else {
                        $btn = 'Not Authorized';
                    }

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $states       = State::all();
        $alllocations = CompanyLocation::get(); //where('user_id', auth()->user()->added_by)->
        return view('admin.agents.index', compact('states', 'alllocations'));
    }

    /**
     * Store a newly created agent in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'                 => 'required|string|max:255',
                'email'                => 'required|email',
                'priority'             => 'required|integer',
                'daily_limit'          => 'required|integer',
                'monthly_limit'        => 'required|integer',
                'total_limit'          => 'required|integer',
                'npm_number'           => 'required|string',
                'destination_location' => 'required|string',
                'consent'              => 'required|string',
                'states.*'             => 'integer',
                'weightage'            => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            \DB::beginTransaction(); // Start DB Transaction

            // Create Agent
            $agent = Agent::create([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'destination_location' => $validated['destination_location'] ?? null,
                'destination_webhook'  => $request->destination_webhook ?? null,
                'priority'             => $validated['priority'],
                'daily_limit'          => $validated['daily_limit'],
                'total_limit'          => $validated['total_limit'],
                'monthly_limit'        => $validated['monthly_limit'],
                'consent'              => json_encode($validated['consent'] ?? ''),
                'npm_number'           => $validated['npm_number'] ?? null,
                'cross_link'           => $validated['cross_link'] ?? null,
                'weightage'            => $validated['weightage'] ?? null,
                'user_id'              => login_id(),
            ]);

            // Save Agent States
            if (! empty($validated['states'])) {
                foreach ($validated['states'] as $stateId) {
                    AgentState::create([
                        'agent_id' => $agent->id,
                        'state_id' => $stateId,
                        'user_id'  => auth()->id(),
                    ]);
                }
            }

            // Save Carrier Types
            if (! empty($request->carrier_type)) {
                foreach ($request->carrier_type as $type) {
                    AgentCarrierType::create([
                        'agent_id'     => $agent->id,
                        'carrier_type' => $type,
                    ]);
                }
            }

            // Add Agent as User
            $this->addAgentAsUser($agent, $request);

            \DB::commit(); // Commit transaction

            return response()->json(['success' => true, 'message' => 'Agent created successfully!']);
        } catch (\Exception $e) {
            \DB::rollBack(); // Rollback on error

            // Check if the agent was created, and delete if exists
            if (isset($agent) && Agent::where('id', $agent->id)->exists()) {
                Agent::where('id', $agent->id)->delete();
            }

            // Check if the user was created, and delete if exists
            $user = User::where('agent_id', $agent->id)->first();
            if ($user) {
                $user->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the agent!',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function addAgentAsUser($agent, $request)
    {
        if (! is_null($agent->destination_location)) {

            $role        = $request->has('userRoleChecked') && is_role() == 'admin' ? 2 : ($request->has('userRoleChecked') ? 1 : 3);
            $from_agents = $request->has('userRoleChecked') ? 1 : 0;
            AgentUser::where('agent_id', $agent->id)->delete();
            $user = User::updateOrCreate(
                ['agent_id' => $agent->id], // Find user by agent_id
                [
                    'email'       => $agent->email,
                    'name'        => $agent->name,
                    'last_name'   => $agent->name,
                    'password'    => Hash::make('12345678'), // Default password
                    'role'        => $role,
                    'added_by'    => auth()->id(),
                    'location_id' => $agent->destination_location,
                    'from_agents' => $from_agents,
                ]
            );

            if (! empty($request->agent_access)) {
                $agentAccessData = array_map(fn($userId) => [
                    'location_id' => $userId,
                    'user_id'     => $user->id,
                    'agent_id'    => $agent->id,
                ], $request->agent_access);

                AgentUser::insert($agentAccessData); // Bulk insert for efficiency
            }

            if ($user) {
                $token = GhlAuth::where('user_id', 1)->first();
                if ($token) {
                    $locationId = \CRM::connectLocation($token->user_id, $user->location_id, null, $user->id);
                    if (isset($locationId->location_id)) {
                        if ($locationId->statusCode == 400) {
                            \Log::error('Bad Request: Invalid locationId or accessToken', [
                                'location_id' => $user->location_id,
                                'user_id'     => $token->user_id,
                                'response'    => $locationId,
                            ]);
                            return response()->json(['error' => 'Invalid locationId or accessToken'], 400);
                        }

                        $ghl            = GhlAuth::where('location_id', $locationId->location_id)->where('user_id', $user->id)->first();
                        $locationDetail = \CRM::crmV2($token->user_id, 'locations/' . $ghl->location_id, 'get', '', [], false, $ghl->location_id, $ghl);
                        //\Log::info(["locationID" => $locationDetail]);
                        if (isset($locationDetail->location)) {
                            $subAccountDetail = $locationDetail->location;
                            $user             = User::find($user->id);
                            if ($user) {
                                $user->update([
                                    'name'  => $subAccountDetail->name ?? $user->name,
                                    'email' => $subAccountDetail->email ?? $user->email,
                                ]);
                            }
                            \Log::info(["users" => $user]);
                            // Update Agent details
                            // if (isset($agent) && $agent instanceof Agent) {
                            //     $agent->update([
                            //         'name' => $subAccountDetail->name ?? $user->name,
                            //         'email' => $subAccountDetail->email ?? $user->email,
                            //     ]);
                            // }

                        }
                        if ($ghl) {
                            $ghl->name    = $locationId->name ?? '';
                            $ghl->user_id = $user->id ?? '';
                            $ghl->save();
                            \Log::info('Updated GhlAuth record', [
                                'location_id' => $locationId->location_id,
                                'name'        => $user->name,
                            ]);
                        }

                        $apicall = \CRM::crmV2($user->id, 'customFields', 'get', '', [], false, $ghl->location_id, $ghl, $user->id);
                        //dd($apicall);
                        if (isset($apicall->customFields)) {
                            $apiData = $apicall->customFields;
                            // dd($apiData);
                            foreach ($apiData as $field) {
                                // Find existing custom field record
                                $customField = \App\Models\CustomField::where('cf_id', $field->id)->where('location_id', $field->locationId)->first();
                                // Prepare data array with custom field values
                                $customFieldData = [
                                    'cf_id'       => $field->id ?? null,
                                    'cf_name'     => $field->name ?? null,
                                    'cf_key'      => $field->fieldKey ?? null,
                                    'dataType'    => $field->dataType ?? null,
                                    'location_id' => $field->locationId ?? null,
                                ];
                                if ($customField) {
                                    foreach ($customFieldData as $key => $value) {
                                        $customField->$key = $value;
                                    }
                                    $customField->save();
                                } else {
                                    $customField = new CustomField();
                                    foreach ($customFieldData as $key => $value) {
                                        $customField->$key = $value;
                                    }
                                    $customField->save();
                                }
                            }
                        }
                    }
                }
            }

        }
    }

    /**
     * Update the specified agent in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $agent     = Agent::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'name'                 => 'required|string|max:255',
                'email'                => 'required|email',
                'priority'             => 'required|integer',
                'daily_limit'          => 'required|integer',
                'monthly_limit'        => 'required|integer',
                'total_limit'          => 'required|integer',
                'npm_number'           => 'required|string',
                'destination_location' => 'required|string',
                'consent'              => 'required',
                'states'               => 'required|array',
                'states.*'             => 'integer',
                'weightage'            => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            \DB::beginTransaction(); // Start transaction

            // Update agent details
            $agent->update([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'destination_location' => $validated['destination_location'],
                'destination_webhook'  => ! empty($request->destination_webhook) ? $request->destination_webhook : null,
                'priority'             => $validated['priority'],
                'daily_limit'          => $validated['daily_limit'],
                'monthly_limit'        => $validated['monthly_limit'],
                'total_limit'          => $validated['total_limit'],
                'consent'              => json_encode($validated['consent']),
                'npm_number'           => $validated['npm_number'],
                'cross_link'           => $request->cross_link ?? null,
                'weightage'            => $validated['weightage'],
                'user_id'              => login_id(),
            ]);

            // Update associated states
            AgentState::where('agent_id', $agent->id)->delete();
            foreach ($validated['states'] as $stateId) {
                AgentState::create([
                    'agent_id' => $agent->id,
                    'state_id' => $stateId,
                    'user_id'  => auth()->id(),
                ]);
            }

            // Update associated carrier types
            AgentCarrierType::where('agent_id', $agent->id)->delete();
            if (! empty($request->carrier_type)) {
                foreach ($request->carrier_type as $type) {
                    AgentCarrierType::create([
                        'agent_id'     => $agent->id,
                        'carrier_type' => $type,
                    ]);
                }
            }

            // Update Agent User
            $this->addAgentAsUser($agent, $request);

            \DB::commit(); // Commit transaction

            return response()->json(['success' => true, 'message' => 'Agent updated successfully!']);
        } catch (\Exception $e) {
            \DB::rollBack(); // Rollback on error
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the agent!',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified agent from storage.
     */
    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);
        // Remove associated states
        AgentState::where('agent_id', $agent->id)->delete();
        User::where('agent_id', $agent->id)->delete();
        \App\Models\AgentCarrierType::where('agent_id', $agent->id)->delete();
        $agent->delete();
        return response()->json(['success' => 'Agent deleted successfully!']);
    }
    public function agentUserSave(Request $request)
    {
        //dd($request->all());
        $agentId = (int) $request->agent_user;
        //dd($agentUser, $agentCarrierType);
        $agentUserData = new agentUserData();
        $agentUserData->agentUserDatasave($agentId);
    }

    public function agent($id)
    {
        $agent = Agent::find($id);
        if ($agent) {
            $agent->status = ! $agent->status;
            $agent->save();
            return response()->json(['succes' => true, "message" => "agent status changed successfully"]);
        }
        return response()->json(['success' => true, "message" => "agent not found"], 400);
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
            if ($campaignId == null && $agentId == 'all') {
                return response()->json([
                    'success'  => true,
                    'message'  => "Get All data successfully.",
                    'data'     => $data,
                    'redirect' => route('admin.dashboard'),
                ], 200);
                return redirect()->route('admin.dashboard');
            }
            if (! is_null($campaignId) && $campaignId !== 'all') {
                // This block runs only if $campaignId is NOT null and NOT 'all'

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
                    ? ' AND contacts.created_at BETWEEN "' . $startDate . '" AND "' . $endDate . '"'
                    : ''
                ) . ') as total_contacts_count')
            )
                ->whereIn('agents.id', $agentId)
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

            $data = $query->get();

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
    public function agentUser()
    {
        $agentUser = Agent::select("name", 'id')->where('user_id', '!=', Auth::user()->id)->get();
        return response()->json(['success' => true, "message" => "Agent Fetch Successfully", "data" => $agentUser]);
    }
    public function searchAgentByAjax(Request $request){

        $campaign_id = $request->campaign_id;
        \Log::info(["campaign_id" => $campaign_id]);
        $term = $request->q ?? ($request->term ?? ($request->search ?? ''));
        $data = Agent::query()->select('name as text', 'id')->where('user_id',login_id());
        if($campaign_id == '' || $campaign_id == null ){
            if (!empty($term)) {
                $data->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
                });
           }
        }
        else{
            $agentCampaign = CampaignAgent::where('campaign_id',$campaign_id)->pluck('agent_id')->toArray();
            $data->whereIn('id', $agentCampaign);
            \Log::info("condation is working");
            if (!empty($term)) {
                $data->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
                });
           }
           \Log::info(["Campaign id is woke show data" =>$data->get() ]);

        }
        $results = $data->take(100)->get();
        return response()->json($results);
    }
}
