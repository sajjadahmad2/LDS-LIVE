<?php
namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentCarrierType;
use App\Models\AgentLeadType;
use App\Models\AgentState;
use App\Models\AgentUser;
use App\Models\CampaignAgent;
use App\Models\Campaign;
use App\Models\CompanyLocation;
use App\Models\CustomField;
use App\Models\GhlAuth;
use App\Models\LeadType;
use App\Models\State;
use App\Models\User;
use App\Services\agentUserData;
use Auth;
use Carbon\Carbon;
use DataTables;
use DB;
use Illuminate\Support\Arr;
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
            $userId = login_id();
            $user   = Auth::user();

            $query = Agent::query()
                ->with([
                    'states.leadType:id,name',
                    'carrierTypes.leadType:id,name',
                    'agentLeadTypes.leadType:id,name',
                ])
                ->select(
                    'id',
                    'name',
                    'email',
                    'user_id',
                    'created_at'
                )
                ->when($user->role == 1, function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }, function ($q) use ($userId) {
                    $q->whereIn('destination_location', AgentUser::where('user_id', $userId)->pluck('location_id'));
                });

            // Filtering
            if ($request->filled('agent_ids') && $request->agent_ids !== 'all') {
                $query->where('id', $request->agent_ids);
            }

            if ($request->filled('state_ids') && $request->state_ids !== 'all') {
                $agentIds = AgentState::where('state_id', $request->state_ids)->pluck('agent_id');
                $query->whereIn('id', $agentIds);
            }

            if ($request->filled('campaign_ids') && $request->campaign_ids !== 'all') {
                $agentCampaignIds = CampaignAgent::where('campaign_id', $request->campaign_ids)->pluck('agent_id');
                $query->whereIn('id', $agentCampaignIds);
            }

            if ($request->filled('customDateRange') && $request->customDateRange !== 'all') {
                $dates = explode(' to ', $request->customDateRange);
                if (count($dates) === 2) {
                    $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]), 'America/Chicago')->startOfDay();
                    $endDate   = Carbon::createFromFormat('Y-m-d', trim($dates[1]), 'America/Chicago')->endOfDay();
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            }

            if (! empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('destination_location', 'like', "%{$search}%")
                        ->orWhereHas('states', function ($q) use ($search) {
                            $q->where('state', 'like', "%{$search}%");
                        })
                        ->orWhereHas('leadTypes', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            return DataTables::of($query->get())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    if (! in_array(is_role(), ['superadmin', 'admin'])) {
                        return 'Not Authorized';
                    }

                    $user        = User::where('agent_id', $row->id)->first();
                    $locationIds = $user && $user->role == 2
                        ? AgentUser::where('user_id', $user->id)->pluck('location_id')->toArray()
                        : [];
                    $consents            = json_encode($row->agentLeadTypes->map(fn($lt) => ['consent' => $lt->consent, 'lead_type_id' => $lt->lead_type]));
                    $totalLimits         = json_encode($row->agentLeadTypes->map(fn($lt) => ['total_limit' => $lt->total_limit, 'lead_type_id' => $lt->lead_type]));
                    $dailyLimits         = json_encode($row->agentLeadTypes->map(fn($lt) => ['daily_limit' => $lt->daily_limit, 'lead_type_id' => $lt->lead_type]));
                    $destinationLocation = json_encode($row->agentLeadTypes->map(fn($lt) => ['destination_location' => $lt->destination_location, 'lead_type_id' => $lt->lead_type]));
                    $destinationWebhook  = json_encode($row->agentLeadTypes->map(fn($lt) => ['destination_webhook' => $lt->destination_webhook, 'lead_type_id' => $lt->lead_type]));
                    $monthlyLimits       = json_encode($row->agentLeadTypes->map(fn($lt) => ['monthly_limit' => $lt->monthly_limit, 'lead_type_id' => $lt->lead_type]));
                    $crossLinks          = json_encode($row->agentLeadTypes->map(fn($lt) => ['cross_link' => $lt->cross_link, 'lead_type_id' => $lt->lead_type]));
                    $npmNumbers          = json_encode($row->agentLeadTypes->map(fn($lt) => ['npm_number' => $lt->npm_number, 'lead_type_id' => $lt->lead_type]));
                    $carrierTypes        = json_encode($row->carrierTypes->map(fn($c) => [
                        'carrier_type' => $c->carrier_type,
                        'lead_type_id' => $c->lead_type,
                    ]), JSON_HEX_APOS | JSON_HEX_QUOT);
                    $states = json_encode($row->states->map(fn($s) => [
                        'state_id'     => $s->state_id,
                        'lead_type_id' => $s->lead_type,
                    ]), JSON_HEX_APOS | JSON_HEX_QUOT);

                    $leadTypes = json_encode(
                        $row->agentLeadTypes
                            ? $row->agentLeadTypes->pluck('leadType.id')
                            : [],
                        JSON_HEX_APOS | JSON_HEX_QUOT
                    );

                    $locations = json_encode($locationIds, JSON_HEX_APOS | JSON_HEX_QUOT);

                    return '
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#agentModal"
   onclick="savaAgentData('
                    . $row->id . ', '
                    . '\'' . addslashes($row->name) . '\', '
                    . '\'' . addslashes($row->email) . '\', '
                    . htmlspecialchars($destinationLocation, ENT_QUOTES) . ', '
                    . htmlspecialchars($destinationWebhook, ENT_QUOTES) . ', '
                    . htmlspecialchars($dailyLimits, ENT_QUOTES) . ', '
                    . htmlspecialchars($monthlyLimits, ENT_QUOTES) . ', '
                    . htmlspecialchars($totalLimits, ENT_QUOTES) . ', '
                    . htmlspecialchars($consents, ENT_QUOTES) . ', '
                    . htmlspecialchars($carrierTypes, ENT_QUOTES) . ', '
                    . htmlspecialchars($states, ENT_QUOTES) . ', '
                    . htmlspecialchars($npmNumbers, ENT_QUOTES) . ', '
                    . htmlspecialchars($crossLinks, ENT_QUOTES) . ', '
                    . (! empty($locationIds) ? 'true' : 'false') . '
   )">
    <i class="bx bx-edit"></i> Edit
</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item" onclick="deleteAgent(' . $row->id . ')">
                                <i class="bx bx-trash"></i> Delete
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item status_changes" data-status="' . $row->id . '">
                                <i class="bx bxs-low-vision"></i> Change Status
                            </a>
                        </li>
                    </ul>
                </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $states       = State::select('id', 'state')->get();
        $alllocations = CompanyLocation::pluck('id', 'location_name')->toArray();
        dd($alllocations);
        $leadTypes    = LeadType::select('id', 'name')->get();
        $carrierTypes=getAllCarrierType();

        return view('admin.agents.index', compact('states', 'alllocations', 'leadTypes','carrierTypes'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'                              => 'required|string|max:255',
                'email'                             => 'required|email',
                'lead_types'                        => 'required|array',
                'lead_types.*.id'                   => 'required|integer',
                'lead_types.*.daily_limit'          => 'nullable|integer',
                'lead_types.*.monthly_limit'        => 'nullable|integer',
                'lead_types.*.total_limit'          => 'nullable|integer',
                'lead_types.*.npm_number'           => 'nullable|string',
                'lead_types.*.cross_link'           => 'nullable|string',
                'lead_types.*.consent'              => 'nullable|string',
                'lead_types.*.states'               => 'array|nullable',
                'lead_types.*.carrier_type'         => 'array|nullable',
                'lead_types.*.destination_location' => 'nullable|string',
                'lead_types.*.destination_webhook'  => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            \DB::beginTransaction();

            // Create Agent (only general info)
            $agent = Agent::create([
                'name'    => $validated['name'],
                'email'   => $validated['email'],
                // 'destination_location' => $validated['destination_location'],
                // 'destination_webhook'  => $validated['destination_webhook'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Loop through each lead type
            foreach ($validated['lead_types'] as $lead) {
                // Check if ALL relevant fields are empty
                $allEmpty = empty($lead['daily_limit'])
                && empty($lead['monthly_limit'])
                && empty($lead['total_limit'])
                && empty($lead['npm_number'])
                && empty($lead['cross_link'])
                && empty($lead['consent'])
                    && (empty($lead['states']) || count($lead['states']) === 0)
                    && (empty($lead['carrier_type']) || count($lead['carrier_type']) === 0);

                if ($allEmpty) {
                    continue; // Skip this lead type entirely
                }
                $agentLocations = [];
                // Save the lead type record
                $agentLead = AgentLeadType::create([
                    'agent_id'             => $agent->id,
                    'lead_type'            => $lead['id'],
                    'daily_limit'          => $lead['daily_limit'] ?? null,
                    'monthly_limit'        => $lead['monthly_limit'] ?? null,
                    'total_limit'          => $lead['total_limit'] ?? null,
                    'npm_number'           => $lead['npm_number'] ?? null,
                    'cross_link'           => $lead['cross_link'] ?? null,
                    'consent'              => $lead['consent'] ?? null,
                    'destination_location' => $lead['destination_location'] ?? null,
                    'destination_webhook'  => $lead['destination_webhook'] ?? null,
                ]);
                if(isset($lead['destination_location']) && !empty($lead['destination_location'])){
                    $agentLocations[] = $lead['destination_location'];
                }

                // Save states
                if (! empty($lead['states'])) {
                    foreach ($lead['states'] as $stateId) {
                        AgentState::create([
                            'agent_id'  => $agent->id,
                            'state_id'  => $stateId,
                            'lead_type' => $lead['id'],
                            'user_id'   => auth()->id(),
                        ]);
                    }
                }

                // Save carriers
                if (! empty($lead['carrier_type'])) {
                    foreach ($lead['carrier_type'] as $carrier) {
                        AgentCarrierType::create([
                            'agent_id'     => $agent->id,
                            'carrier_type' => $carrier,
                            'lead_type'    => $lead['id'],
                        ]);
                    }
                }
            }

            // Assign Agent as User
            $this->agentLocationsToken($agentLocations, $agent);
            $this->addAgentAsUser($agent, $request);

            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Agent created successfully!']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the agent!',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $agent = Agent::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'                              => 'required|string|max:255',
                'email'                             => 'required|email',
                'lead_types'                        => 'required|array',
                'lead_types.*.id'                   => 'required|integer',
                'lead_types.*.daily_limit'          => 'nullable|integer',
                'lead_types.*.monthly_limit'        => 'nullable|integer',
                'lead_types.*.total_limit'          => 'nullable|integer',
                'lead_types.*.npm_number'           => 'nullable|string',
                'lead_types.*.cross_link'           => 'nullable|string',
                'lead_types.*.consent'              => 'nullable|string',
                'lead_types.*.states'               => 'array|nullable',
                'lead_types.*.carrier_type'         => 'array|nullable',
                'lead_types.*.destination_location' => 'nullable|string',
                'lead_types.*.destination_webhook'  => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            \DB::beginTransaction();

            // Update agent
            $agent->update([
                'name'    => $validated['name'],
                'email'   => $validated['email'],
                // 'destination_location' => $validated['destination_location'],
                // 'destination_webhook'  => $validated['destination_webhook'] ?? null,
                'user_id' => login_id(),
            ]);
            $agentLocations = [];
            // Clean old records
            AgentLeadType::where('agent_id', $agent->id)->delete();
            AgentState::where('agent_id', $agent->id)->delete();
            AgentCarrierType::where('agent_id', $agent->id)->delete();
            AgentUser::where('agent_id', $agent->id)->delete();
            // Recreate fresh
            foreach ($validated['lead_types'] as $lead) {
                // Check if ALL relevant fields are empty
                $allEmpty = empty($lead['daily_limit'])
                && empty($lead['monthly_limit'])
                && empty($lead['total_limit'])
                && empty($lead['npm_number'])
                && empty($lead['cross_link'])
                && empty($lead['consent'])
                    && (empty($lead['states']) || count($lead['states']) === 0)
                    && (empty($lead['carrier_type']) || count($lead['carrier_type']) === 0);

                if ($allEmpty) {
                    continue; // Skip this lead type entirely
                }

                // Save the lead type record
                $agentLead = AgentLeadType::create([
                    'agent_id'             => $agent->id,
                    'lead_type'            => $lead['id'],
                    'daily_limit'          => $lead['daily_limit'] ?? null,
                    'monthly_limit'        => $lead['monthly_limit'] ?? null,
                    'total_limit'          => $lead['total_limit'] ?? null,
                    'npm_number'           => $lead['npm_number'] ?? null,
                    'cross_link'           => $lead['cross_link'] ?? null,
                    'consent'              => $lead['consent'] ?? null,
                    'destination_location' => $lead['destination_location'] ?? null,
                    'destination_webhook'  => $lead['destination_webhook'] ?? null,
                ]);

                if(isset($lead['destination_location']) && !empty($lead['destination_location'])){
                    $agentLocations[] = $lead['destination_location'];
                }

                // Save states
                if (! empty($lead['states'])) {
                    foreach ($lead['states'] as $stateId) {
                        AgentState::create([
                            'agent_id'  => $agent->id,
                            'state_id'  => $stateId,
                            'lead_type' => $lead['id'],
                            'user_id'   => auth()->id(),
                        ]);
                    }
                }

                // Save carriers
                if (! empty($lead['carrier_type'])) {
                    foreach ($lead['carrier_type'] as $carrier) {
                        AgentCarrierType::create([
                            'agent_id'     => $agent->id,
                            'carrier_type' => $carrier,
                            'lead_type'    => $lead['id'],
                        ]);
                    }
                }
            }

            // Update agent user
            $this->agentLocationsToken($agentLocations, $agent);
            $this->addAgentAsUser($agent, $request);

            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Agent updated successfully!']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the agent!',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),   // line number of error

            ], 500);
        }
    }
    public function agentLocationsToken($agentLocations = [], $agent)
    {
        if (count($agentLocations) <= 0 || empty($agent)) {
            return false;

        }
        AgentUser::where('agent_id', $agent->id)->delete();
        foreach ($agentLocations as $location) {
            if (empty($location)) {
                continue;
            }

            $usertoken  = GhlAuth::where('user_id', $agent->user_id)->where('user_type', 'Company')->first();
            $agenttoken = GhlAuth::where('user_id', $agent->user_id)->where('user_type', 'Location')->where('location_id', $location)->first();
            if (empty($usertoken)) {
                return false;
            }

            $locationId = \CRM::connectLocation($usertoken->user_id, $location, $usertoken);

            if (isset($locationId->location_id)) {
                if ($locationId->statusCode == 400) {
                    \Log::error('Bad Request: Invalid locationId or accessToken', [
                        'location_id' => $location,
                        'user_id'     => $agent->user_id,
                        'response'    => $locationId,
                    ]);
                    return response()->json(['error' => 'Invalid locationId or accessToken'], 400);
                }

                $ghl            = GhlAuth::where('location_id', $locationId->location_id)->where('user_id', $agent->user_id)->first();
                $locationDetail = \CRM::crmV2($agent->user_id, 'locations/' . $ghl->location_id, 'get', '', [], false, $ghl->location_id, $ghl);
                if (isset($locationDetail->location)) {
                    $subAccountDetail = $locationDetail->location;
                }
                if ($subAccountDetail) {
                    $ghl->name = $subAccountDetail->location->name ?? '';
                    $ghl->save();
                    \Log::info('Updated GhlAuth record', [
                        'location_id' => $locationId->location_id,
                    ]);
                }
                $apicall = \CRM::crmV2($agent->user_id, 'customFields', 'get', '', [], false, $ghl->location_id, $ghl, $agent->user_id);
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
            return true;
        }
    }
    public function addAgentAsUser($agent, $request)
    {
        if ($request->has('userRoleChecked') && $request->userRoleChecked == 'on' && ! empty($agent)) {
            $role        = $request->has('userRoleChecked') && is_role() == 'admin' ? 3 : ($request->has('userRoleChecked') ?  1 : 3);
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

            // if ($user) {
            //     $token = GhlAuth::where('user_id', $user->id)->where('user_type', 'Company')->first();
            //     if ($token) {
            //         $locationId = \CRM::connectLocation($token->user_id, $user->location_id, null, $user->id);
            //         if (isset($locationId->location_id)) {
            //             if ($locationId->statusCode == 400) {
            //                 \Log::error('Bad Request: Invalid locationId or accessToken', [
            //                     'location_id' => $user->location_id,
            //                     'user_id'     => $token->user_id,
            //                     'response'    => $locationId,
            //                 ]);
            //                 return response()->json(['error' => 'Invalid locationId or accessToken'], 400);
            //             }

            //             $ghl            = GhlAuth::where('location_id', $locationId->location_id)->where('user_id', $user->id)->first();
            //             $locationDetail = \CRM::crmV2($token->user_id, 'locations/' . $ghl->location_id, 'get', '', [], false, $ghl->location_id, $ghl);
            //             //\Log::info(["locationID" => $locationDetail]);
            //             if (isset($locationDetail->location)) {
            //                 $subAccountDetail = $locationDetail->location;
            //                 $user             = User::find($user->id);
            //                 if ($user) {
            //                     $user->update([
            //                         'name'  => $subAccountDetail->name ?? $user->name,
            //                         'email' => $subAccountDetail->email ?? $user->email,
            //                     ]);
            //                 }
            //                 \Log::info(["users" => $user]);
            //                 // Update Agent details
            //                 // if (isset($agent) && $agent instanceof Agent) {
            //                 //     $agent->update([
            //                 //         'name' => $subAccountDetail->name ?? $user->name,
            //                 //         'email' => $subAccountDetail->email ?? $user->email,
            //                 //     ]);
            //                 // }

            //             }
            //             if ($ghl) {
            //                 $ghl->name    = $locationId->name ?? '';
            //                 $ghl->user_id = $user->id ?? '';
            //                 $ghl->save();
            //                 \Log::info('Updated GhlAuth record', [
            //                     'location_id' => $locationId->location_id,
            //                     'name'        => $user->name,
            //                 ]);
            //             }

            //         }
            //     }
            // }

        }
    }

    /**
     * Update the specified agent in storage.
     */

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
        \App\Models\AgentLeadType::where('agent_id', $agent->id)->delete();
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

    public function agentCampaignSearch(Request $request)
    {
        // Retrieve parameters
        $type          = $request->query('type');
        $dateRange     = $request->query('dateRange');
        $reqAgentId    = $request->query('agentId');
        $reqCampaignId = $request->query('campaignId');
        $leadTypeId    = $request->query('lead_type') ?? 1; // 👈 now taken from request (default = 1)
        $startDate     = null;
        $endDate       = null;
        // Handle date range
        $currentDate = Carbon::now('America/Chicago')->format('Y-m-d');
        if ($dateRange) {
            $dates     = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('m/d/Y', $dates[0], 'America/Chicago')->startOfDay()->format('Y-m-d H:i:s');
            $endDate   = Carbon::createFromFormat('m/d/Y', $dates[1], 'America/Chicago')->endOfDay()->format('Y-m-d H:i:s');
        }
        // else {
        //     // Default to current month range up to now
        //     $startDate = Carbon::now('America/Chicago')->startOfMonth()->format('Y-m-d H:i:s');
        //     $endDate   = Carbon::now('America/Chicago')->endOfDay()->format('Y-m-d H:i:s');
        // }

        $data = null;

        if (in_array($type, ['agent', 'campaign', 'lead_type'])) {

            // Initialize an empty list
            $agentId = [];

            // ✅ CASE 1: Only lead type selected (no campaign or agent)
            if (
                in_array($type, ['lead_type', 'agent', 'campaign'], true) &&
                (empty($reqCampaignId) || $reqCampaignId === 'all') &&
                (empty($reqAgentId) || $reqAgentId === 'all')
            ) {
                // Fetch ALL agent IDs for this user
                $agentId = Agent::where('user_id', login_id())->pluck('id')->toArray();

            }

            // ✅ CASE 2: Campaign is selected
            elseif (! is_null($reqCampaignId) && $reqCampaignId !== 'all') {
                $campaign = Campaign::find($reqCampaignId);
                if ($campaign) {
                    // If campaign defines its own lead type, override
                    $leadTypeId = $campaign->lead_type ?? $leadTypeId;
                }

                $agentId = CampaignAgent::where('campaign_id', $reqCampaignId)
                    ->pluck('agent_id')
                    ->toArray();
            }

            // ✅ CASE 3: Single agent selected
            elseif (! is_null($reqAgentId) && $reqAgentId !== 'all') {

                $agentId = [$reqAgentId];
            }

            $agentId = Arr::flatten($agentId);
            // ✅ Now build query — always filtered by lead type

            $query = Agent::select(
                'agents.id',
                'agents.name',
                'campaign_agents.priority',
                'agent_lead_types.daily_limit',
                'agent_lead_types.monthly_limit',
                'agent_lead_types.total_limit',
                DB::raw('(
        SELECT COUNT(*)
        FROM contacts
        WHERE contacts.agent_id = agents.id
        AND contacts.lead_type = ' . (int) $leadTypeId . '
        AND MONTH(contacts.created_at) = MONTH(CURRENT_DATE)
    ) as monthly_contacts_count'),
                DB::raw('(
        SELECT COUNT(*)
        FROM contacts
        WHERE contacts.agent_id = agents.id
        AND contacts.lead_type = ' . (int) $leadTypeId . '
        AND DATE(contacts.created_at) = CURRENT_DATE
    ) as daily_contacts_count'),
                // 👇 Total contacts conditionally based on date range
                DB::raw('(
        SELECT COUNT(*)
        FROM contacts
        WHERE contacts.agent_id = agents.id
        AND contacts.lead_type = ' . (int) $leadTypeId . '
        ' . (
                    // if both dates exist, apply between filter; else count all
                    (! empty($startDate) && ! empty($endDate)
                            ? 'AND contacts.created_at BETWEEN "' . $startDate . '" AND "' . $endDate . '"'
                            : ''
                    )
                ) . '
    ) as total_contacts_count')
            )
                ->leftJoin('campaign_agents', 'campaign_agents.agent_id', '=', 'agents.id')
                ->join('agent_lead_types', function ($join) use ($leadTypeId) {
                    $join->on('agent_lead_types.agent_id', '=', 'agents.id')
                        ->where('agent_lead_types.lead_type', '=', $leadTypeId);
                })
                ->whereIn('agents.id', (array) $agentId) // ensure not nested array
                ->groupBy(
                    'agents.id',
                    'agents.name',
                    'campaign_agents.priority',
                    'agent_lead_types.daily_limit',
                    'agent_lead_types.monthly_limit',
                    'agent_lead_types.total_limit'
                )
                ->orderBy('campaign_agents.priority', 'asc')
                ->orderByRaw('(agent_lead_types.monthly_limit - monthly_contacts_count) desc')
                ->orderByRaw('(agent_lead_types.daily_limit - daily_contacts_count) desc')
                ->orderByRaw('(agent_lead_types.total_limit - total_contacts_count) desc');

            $data = $query->get();

        }

        if (empty($data) || ($type !== 'all' && $data->isEmpty())) {
            return response()->json([
                'success' => true,
                'message' => "No $type data found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "$type data fetched successfully.",
            'data'    => $data,
        ], 200);
    }
    public function agentUser()
    {
        $agentUser = Agent::select("name", 'id')->where('user_id', '!=', Auth::user()->id)->get();
        return response()->json(['success' => true, "message" => "Agent Fetch Successfully", "data" => $agentUser]);
    }
    public function searchAgentByAjax(Request $request)
    {

        $campaign_id = $request->campaign_id;
        \Log::info(["campaign_id" => $campaign_id]);
        $term = $request->q ?? ($request->term ?? ($request->search ?? ''));
        $data = Agent::query()->select('name as text', 'id')->where('user_id', login_id());
        if ($campaign_id == '' || $campaign_id == null) {
            if (! empty($term)) {
                $data->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
                });
            }
        } else {
            $agentCampaign = CampaignAgent::where('campaign_id', $campaign_id)->pluck('agent_id')->toArray();
            $data->whereIn('id', $agentCampaign);
            \Log::info("condition is working");
            if (! empty($term)) {
                $data->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
                });
            }
            \Log::info(["Campaign id is woke show data" => $data->get()]);

        }
        $results = $data->take(100)->get();
        return response()->json($results);
    }
}
