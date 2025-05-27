<?php
namespace App\Services;

use App\Models\Agent;
use App\Models\AgentCarrierType;
use App\Models\AgentState;
use App\Models\AgentUser;
use App\Models\GhlAuth;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentUserData
{
    public function agentUserDatasave($agentId)
    {
        $agent = Agent::find($agentId);

        if (! $agent) {
            Log::error("Agent not found: ID " . $agentId);
            return response()->json(['message' => 'Agent not found'], 404);
        }
        $userId            = Auth::id();
        $agentStates       = AgentState::where('agent_id', $agent->id)->get();
        $agentCarrierTypes = AgentCarrierType::where('agent_id', $agent->id)->get();

        $newAgent          = $agent->replicate();
        $newAgent->user_id = $userId;
        $newAgent->save();

        // ✅ Save agent states for the user
        foreach ($agentStates as $state) {
            AgentState::updateOrCreate(
                ['agent_id' => $newAgent->id, 'state_id' => $state->state_id, 'user_id' => $userId],
                ['updated_at' => now()]
            );
        }

        // ✅ Save carrier types for the user
        foreach ($agentCarrierTypes as $carrierType) {
            AgentCarrierType::updateOrCreate(
                ['agent_id' => $newAgent->id, 'carrier_type' => $carrierType->carrier_type],
                ['updated_at' => now()]
            );
        }

        Log::info("Agent data saved for user $userId", [
            'agent_id'      => $newAgent->id,
            'states'        => $agentStates->pluck('state_id')->toArray(),
            'carrier_types' => $agentCarrierTypes->pluck('carrier_type')->toArray(),
        ]);

        if (! is_null($newAgent->destination_location)) {

            $user1      = User::where('email', $agent->email)->where('name', $agent->name)->first();
            $added_by   = auth()->id();
            $from_agent = AgentUser::where('agent_id', $agent->id)->get();
            if (count($from_agent) > 0) {
                $from_agent = 1;
            } else {
                $from_agent = 0;
            }
            if (!$from_agent) {
                $user = User::create([
                    'email'       => $newAgent->email,
                    'name'        => $newAgent->name,
                    'last_name'   => $newAgent->name,
                    'password'    => \Hash::make('12345678'), // Default password
                    'role'        => 3,
                    'added_by'    => $added_by,
                    'agent_id'    => $newAgent->id,
                    'location_id' => $newAgent->destination_location,
                    'from_agents' => $from_agent,
                ]);
                $user_id      = $user->id;
                $agent_access = AgentUser::where(['location_id' => $newAgent->destination_location, 'user_id' => $user_id])->get();
                // Check if access exists (count > 0)
                if ($agent_access->count() > 0) {
                    foreach ($request->agent_access as $userId) {
                        AgentUser::create([
                            'location_id' => $userId,
                            'user_id'     => $user_id,
                        ]);
                    }
                }
                if ($user) {
                    $token = GhlAuth::where('user_id', 1)->first();
                    //dd($token);
                    if ($token) {
                        $locationId = \CRM::connectLocation($token->user_id, $newAgent->destination_location, '', $user->id);
                        //dd( $locationId);
                        if (isset($locationId->location_id)) {
                            if ($locationId->statusCode == 400) {
                                \Log::error('Bad Request: Invalid locationId or accessToken', [
                                    'location_id' => $user->location_id,
                                    'user_id'     => $token->user_id,
                                    'response'    => $locationId,
                                ]);
                                return response()->json(['error' => 'Invalid locationId or accessToken'], 400);
                            }

                            $ghl  = GhlAuth::where('location_id', $locationId->location_id)->where('user_id',$user->id)->first();
                            $locationDetail = \CRM::crmV2($token->user_id, 'locations/' . $ghl->location_id, 'get', '', [], false, $ghl->location_id, $ghl);
                            \Log::info(['locationID' => $locationDetail]);
                            if (isset($locationDetail->location)) {
                                $subAccountDetail = $locationDetail->location;
                                $user             = User::find($user_id);
                                if ($user) {
                                    $user->update([
                                        'name'  => $subAccountDetail->name ?? $user->name,
                                        'email' => $subAccountDetail->email ?? $user->email,
                                    ]);
                                }
                                \Log::info(['users' => $user]);
                                // Update Agent details
                                // if (isset($newAgent) && $newAgent instanceof Agent) {
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

                            $apicall = \CRM::crmV2($user_id, 'customFields', 'get', '', [], false, $ghl->location_id, $ghl);
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

        return response()->json(['message' => 'Agent data saved successfully']);
    }
}
