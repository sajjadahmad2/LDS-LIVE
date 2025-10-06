<?php
namespace App\Jobs;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\CampaignAgent;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\GhlAuth;
use App\Models\Log as Logs;
use App\Models\ProccessContact;
use App\Models\ReserveContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessWebhookData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookdata, $campaign_id;

    /**
     * Create a new job instance.
     */
    public function __construct($webhookdata, $campaign_id)
    {
        $this->webhookdata = $webhookdata;
        $this->campaign_id = $campaign_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
              \Log::info('ProcessWebhookData  Ye wala : ');
        $webhookdata = $this->webhookdata;
        $campaign_id = $this->campaign_id;
        $contact_id  = $webhookdata['contact_id'] ?? null;
        $email       = $webhookdata['email'] ?? null;
        $state       = $webhookdata['state'] ?? null;
        // $lead_type   = $webhookdata['customData']['lead_type'] ?? 1;
        // $leadTypeId  = findLeadTypeId($lead_type);
        \Log::info('ProcessWebhookData  Ye wala : ' . json_encode($webhookdata));
        // Fetch campaign and agent details
        $mainCampaign = Campaign::find($campaign_id);
        $leadTypeId = $mainCampaign->lead_type ?? NULL;
         appendJobLog($contact_id, @json_encode($webhookdata));
         appendJobLog($contact_id, 'Lead Type: ' . $leadTypeId);
         appendJobLog($contact_id, 'Campaign: ' . $campaign_id);
        $agentIds     = CampaignAgent::where('campaign_id', $campaign_id)
            ->whereHas('agent.states', function ($query) use ($state, $leadTypeId) {
                $query->whereHas('state', function ($q) use ($state) {
                    $q->where(DB::raw('TRIM(LOWER(state))'), strtolower($state))
                        ->orWhere(DB::raw('TRIM(LOWER(short_form))'), strtolower($state));
                })->where('lead_type', $leadTypeId);
            })
            ->pluck('agent_id')->toArray();

        if (! $this->validateInitialConditions($webhookdata, $contact_id, $mainCampaign, $agentIds, $leadTypeId)) {
            return;
        }

        // Check for duplicates
        $duplicateContact = $this->checkForDuplicateContact($email, $contact_id);
        if ($duplicateContact) {
            // If duplicate found, assign to another agent
            $this->assignLeadToAnotherAgent($duplicateContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata, $leadTypeId);
            return; // Stop further processing as duplicate has been handled
        }

        // Proceed with normal contact assignment if no duplicate is found
        $proccessContact = $this->getProccessContact($email, $contact_id);

        $this->assignLeadToAgent($proccessContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata, $leadTypeId);
    }

    /**
     * Validate initial conditions such as missing data or agent/campaign info.
     */
    private function validateInitialConditions($webhookdata, $contact_id, $mainCampaign, $agentIds, $leadTypeId)
    {
        if (count($agentIds) === 0 || empty($mainCampaign) || (! isset($webhookdata['state']) && (! isset($contact_id) || is_null($contact_id)))) {
            appendJobLog($contact_id, 'Missing agent, campaign or state, contact sent to reserve.');
            $this->ReserveContact($webhookdata, null, $mainCampaign, 'Missing agent, campaign, or state.', $leadTypeId);
            return false;
        }
        return true;
    }

    /**
     * Check if the contact is duplicated by looking at email or contact_id with SENT status.
     */
    private function checkForDuplicateContact($email, $contact_id)
    {
        return Contact::where(function ($query) use ($email, $contact_id) {
            if ($email) {
                $query->where('email', $email);
            }
            if ($contact_id) {
                $query->orWhere('contact_id', $contact_id);
            }
        })
            ->where('status', 'SENT')
            ->first();

    }

    /**
     * Get the ProccessContact or handle assigning a new one.
     */
    private function getProccessContact($email, $contact_id)
    {
        return ProccessContact::where(function ($query) use ($email, $contact_id) {
            if ($email) {
                $query->where('email', $email);
            }
            if ($contact_id) {
                $query->orWhere('contact_id', $contact_id);
            }
        })
            ->whereNull('agent_id')
            ->first();
    }

    /**
     * Assign lead to another agent if a duplicate contact is found.
     */
    private function assignLeadToAnotherAgent($duplicateContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata, $leadTypeId)
    {
        // Get the agent ID who was originally assigned to this contact
        $originalAgentId = $duplicateContact->agent_id;
        $currentMonth    = Carbon::now('America/Chicago')->month;
        $currentDate     = Carbon::now('America/Chicago')->format('Y-m-d');
        // Fetch all eligible agents excluding the original one
        $agents = CampaignAgent::whereIn('agent_id', $agentIds)
            ->where('agent_id', '!=', $originalAgentId) // exclude original agent
            ->with([
                'agent' => function ($query) use ($currentMonth, $currentDate,$leadTypeId) {
                    $query->withCount([
                        'contacts as monthly_contacts_count' => function ($q) use ($currentMonth) {
                            $q->where('status', 'Sent')->where('lead_type', $leadTypeId)->whereMonth('created_at', $currentMonth);
                        },
                        'contacts as daily_contacts_count'   => function ($q) use ($currentDate) {
                            $q->where('status', 'Sent')->where('lead_type', $leadTypeId)->whereDate('created_at', $currentDate);
                        },
                        'contacts as total_contacts_count'   => function ($q) {
                            $q->where('status', 'Sent')->where('lead_type', $leadTypeId);
                        },
                    ]);
                }, 'agent.agentLeadTypes' => function ($query) use ($leadTypeId) {
                    $query->where('lead_type', $leadTypeId);
                },
            ])
            ->orderBy('priority', 'asc') // campaign-level priority
            ->orderByDesc('weightage')   // campaign-level weightage
            ->get();

        if ($agents->isEmpty()) {
            appendJobLog($contact_id, 'No available agents other than the original one. Sent to reserve.');
            $this->ReserveContact($webhookdata, null, $mainCampaign, 'No available agents for that state other than the original one.', $leadTypeId);
            return;
        }

        // Assign the lead to the first available agent
        $this->assignLeadToBestAgent($agents, $contact_id, $email, $webhookdata, $mainCampaign, $leadTypeId);
    }

    /**
     * Assign lead to the best available agent based on their limits.
     */
    private function assignLeadToBestAgent($agents, $contact_id, $email, $webhookdata, $mainCampaign, $leadTypeId)
    {
        $groupedAgents     = $agents->groupBy('priority');
        $dailyLimitReached = false;
        foreach ($groupedAgents as $priority => $priorityAgents) {
            $agent = $this->isAgentAvailable($priority, $priorityAgents);
            if ($agent) {
                appendJobLog($contact_id, 'Lead assigned to new agent: ' . ($agent->name ?? 'Unknown'));
                $this->ProccessContact($webhookdata, $agent, $mainCampaign, $leadTypeId);
                return;
            }
        }

        appendJobLog($contact_id, 'All agents are at full capacity. Sent to reserve.');
        $this->ReserveContact($webhookdata, null, $mainCampaign, 'All agents full, lead sent to reserve.',$leadTypeId);
    }

    /**
     * Check if the agent is within their contact limits.
     */
    private function isAgentAvailable($priority, $priorityAgents)
    {
        $weightageFull = true; // Assume weightage is full for this priority group

        foreach ($priorityAgents as $campaignAgent) {
            $agent     = $campaignAgent->agent;
            $agentData = $agent->agentLeadTypes->first(); // safer than [0]

            if (! $agentData) {
                // No lead type found for this agent, skip
                continue;
            }

            // Check limits before considering the agent
            $total   = $agent->total_contacts_count < $agentData->total_limit;
            $monthly = $agent->monthly_contacts_count < $agentData->monthly_limit;
            $daily   = $agent->daily_contacts_count < $agentData->daily_limit;
            if ($total && $monthly && $daily) {
                if ($campaignAgent->agent_count_weightage < $campaignAgent->weightage) {
                    $campaignAgent->increment('agent_count_weightage', 1);
                    $weightageFull = false;
                    return $agent; // Exit after assigning one lead
                }
            }
        }

        // If all agents in this priority level have full weightage, reset and retry
        if ($weightageFull) {
            foreach ($priorityAgents as $agent) {
                // Check limits again before resetting weightage
                $agent     = $campaignAgent->agent;
                $agentData = $agent->agentLeadTypes->first(); // safer than [0]

                if (! $agentData) {
                    // No lead type found for this agent, skip
                    continue;
                }

                // Check limits before considering the agent
                $total   = $agent->total_contacts_count < $agentData->total_limit;
                $monthly = $agent->monthly_contacts_count < $agentData->monthly_limit;
                $daily   = $agent->daily_contacts_count < $agentData->daily_limit;

                if ($total && $monthly && $daily) {
                    $campaignAgent->update(['agent_count_weightage' => 0]);
                }
            }

            // Retry with the same priority level after weightage reset
            foreach ($priorityAgents as $agent) {
                $agent     = $campaignAgent->agent;
                $agentData = $agent->agentLeadTypes->first(); // safer than [0]

                if (! $agentData) {
                    // No lead type found for this agent, skip
                    continue;
                }

                // Check limits before considering the agent
                $total   = $agent->total_contacts_count < $agentData->total_limit;
                $monthly = $agent->monthly_contacts_count < $agentData->monthly_limit;
                $daily   = $agent->daily_contacts_count < $agentData->daily_limit;

                if ($total && $monthly && $daily) {
                    // Log::info('Weightage reset. Assigning contact to agent ID: ' . ($agent->name ?? ''));
                    $campaignAgent->increment('agent_count_weightage', 1);
                    return $agent;

                }
            }
        }

        return false;
    }

    /**
     * Assign lead to an agent if no duplicate is found.
     */
    private function assignLeadToAgent($proccessContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata, $leadTypeId)
    {

        if (! $proccessContact) {
            appendJobLog($contact_id, 'No contact found in ProccessContact table. Finding the best agent.');
            $agents = $this->getEligibleAgents($agentIds, $leadTypeId);
            if ($agents->isEmpty()) {
                appendJobLog($contact_id, 'No eligible agents found. Sent to reserve.');
                $this->ReserveContact($webhookdata, null, $mainCampaign, 'No eligible agents found.');
                return;
            }

            $this->assignLeadToBestAgent($agents, $contact_id, $email, $webhookdata, $mainCampaign, $leadTypeId);
        } else {
            // If the contact is found, assign it to the agent
            if ($proccessContact->agent_id) {
                $agent = Agent::where('id', $proccessContact->agent_id)->first();
                appendJobLog($contact_id, 'Lead assigned to existing agent: ' . ($agent->name ?? 'Unknown'));
            }
            $this->ProccessContact($webhookdata, $agent, $mainCampaign);
        }
    }

    /**
     * Get eligible agents based on limits.
     */
    private function getEligibleAgents($agentIds, $leadTypeId)
    {
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');

        return CampaignAgent::whereIn('agent_id', $agentIds)
        // ->where('agent_id', '!=', $originalAgentId) // exclude original agent
            ->with([
                'agent' => function ($query) use ($currentMonth, $currentDate,$leadTypeId) {
                    $query->withCount([
                        'contacts as monthly_contacts_count' => function ($q) use ($currentMonth) {
                            $q->where('status', 'Sent')->where('lead_type', $leadTypeId)->whereMonth('created_at', $currentMonth);
                        },
                        'contacts as daily_contacts_count'   => function ($q) use ($currentDate) {
                            $q->where('status', 'Sent')->where('lead_type', $leadTypeId)->whereDate('created_at', $currentDate);
                        },
                        'contacts as total_contacts_count'   => function ($q) {
                            $q->where('status', 'Sent')->where('lead_type', $leadTypeId);
                        },
                    ]);
                }, 'agent.agentLeadTypes' => function ($query) use ($leadTypeId) {
                    $query->where('lead_type', $leadTypeId);
                },
            ])
            ->orderBy('priority', 'asc') // campaign-level priority
            ->orderByDesc('weightage')   // campaign-level weightage
            ->get();
    }
    /**
     * Process the contact and assign it to the agent.
     */
    protected function ProccessContact($webhookdata, $agent, $campaign, $leadTypeId)
    {

        $contact_id      = $webhookdata['contact_id'] ?? null;
        $my_signature    = json_encode($webhookdata['I have reviewed my application information above, and here is my signature.'] ?? null);
        $proccessContact = ProccessContact::where('contact_id', $contact_id)->first();
        $contactData     = CreateContactData($webhookdata, $agent, $campaign, false, false, $leadTypeId);
        if (! $proccessContact) {
            $proccessContact = new ProccessContact();
        }
        foreach ($contactData as $key => $value) {
            $proccessContact->$key = $value;
        }
        $proccessContact->save();
        appendJobLog($contact_id, "Updated  Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
        appendJobLog($contact_id, "Contact processed for agent ID: " . ($agent->name ?? 'Unknown'));
        $this->ContactProcess($proccessContact, $agent, $campaign, $leadTypeId);
    }

    /**
     * Finalize the contact process and send it to GHL.
     */
    protected function ContactProcess($dbContact, $agent, $campaign, $leadTypeId)
    {
        $my_signature = json_encode($this->data['I have reviewed my application information above, and here is my signature.'] ?? null);
        $contact_id   = $dbContact['contact_id'] ?? null;
        $contactData  = createContactData($dbContact, $agent, $campaign, true, true, $leadTypeId);
        $contact      = Contact::where(function ($query) use ($contact_id, $dbContact) {
            $query->where('status', 'NOT SENT');
            if (! empty($dbContact['email'])) {
                $query->orWhere('email', $dbContact['email']);
            }

            if (! empty($dbContact['phone'])) {
                $query->orWhere('phone', $dbContact['phone']);
            }

            // Uncomment if you want to include contact_id in the search
            if (! empty($contact_id)) {
                $query->orWhere('contact_id', $contact_id);
            }
        })->first();
        if (! $contact) {
            $contact = new Contact();
        }
        foreach ($contactData as $key => $value) {
            $contact->$key = $value;
        }
        $contact->save();
        appendJobLog($contact->contact_id, "Contact updated and forwarded to GHL.");
        $this->SendGhl($contact, $agent, $campaign, $leadTypeId);
    }

    /**
     * Send contact data to GHL.
     */
    protected function SendGhl($contact, $agent, $campaign, $leadTypeId)
    {

        $customData = $contact->contact_json;
        $tags       = ! empty($contact->tags) ? explode(',', (string) $contact->tags) : [];
        $tags       = array_merge($tags, ['aca']);
        $newdata    = [
            'locationId'  => $agent->agentLeadTypes->first()->destination_location,
            'firstName'   => $contact->first_name ?? null,
            'lastName'    => $contact->last_name ?? null,
            'email'       => $contact->email ?? null,
            'phone'       => $contact->phone ?? null,
            'tags'        => $tags,
            'address1'    => $contact->address1 ?? null,
            'city'        => $contact->city ?? null,
            'state'       => $contact->state ?? null,
            'postalCode'  => $contact->postal_code ?? null,
            'country'     => $contact->country ?? null,
            'dateOfBirth' => $contact->date_of_birth ?? null,

        ];
        \Log::info('Destination location of  the Agent Id ' . $agent->id ?? '' . ' ' . $agent->agentLeadTypes->first()->destination_location ?? '');
        appendJobLog($contact->contact_id, 'Destination location of  the Agent Id ' . $agent->id ?? '' . ' ' . $agent->agentLeadTypes->first()->destination_location ?? '');
        if ($agent) {
            $token = \App\Models\GhlAuth::where('location_id', $agent->agentLeadTypes->first()->destination_location)->where('user_id', $agent->user_id ?? '')->first();
            if (! $token) {
                sleep(15);
                $token = $this->connectLocationFirst($agent);
            }
            $custom_field            = $this->customFields($customData, $agent);
            $newdata['customFields'] = $custom_field;
            $url                     = 'contacts';
            sleep(15);
            \Log::info('This ApiCall made by  this agent having id : ' . $agent->id ?? '' . 'and the location id: ' . $agent->agentLeadTypes->first()->destination_location ?? '');
            appendJobLog($contact->contact_id, 'This ApiCall made by  this agent having id : ' . $agent->id ?? ''  . 'and the location id: ' . $agent->agentLeadTypes->first()->destination_location ?? '');
            $response = \App\Helpers\CRM::crmV2($agent->user_id, $url, 'POST', $newdata, [], false, $agent->agentLeadTypes->first()->destination_location);

            \Log::info('response.', ['url' => $url, 'response' => $response]);
            if ($response && property_exists($response, 'contact')) {
                $contact->status = 'Sent';
                $contact->save();
                appendJobLog($contact->contact_id, 'Contact sent Successfully to that agent  : ' . $agent->id . 'and the location id: ' . $agent->agentLeadTypes->first()->destination_location ?? '');

            } else {
                //find campaign agent using the campaign and agent
                $campagent = CampaignAgent::where('agent_id', $agent->id)->where('campaign_id', $campaign->id)->first();
                $campagent->decrement('agent_count_weightage', 1);
                $contactId = $contact->id;
                $contact   = Contact::where('id', $contactId)->first();
                \Log::info('Contact Not submitted or Sent Due to this reason ' . json_encode($response));
                \Log::info('This contact is delete  from the Contact and  process table sent to resever contact ' . $contactId);
                appendJobLog($contact->contact_id, 'Contact Not submitted or Sent Due to this reason ' . json_encode($response));
                $message = json_decode(base64_decode($contact->contact_json), true);
                Logs::updateOrCreate(
                    ['contact_id' => $contact->contact_id],
                    [
                        'contact_id' => $contact->contact_id,
                        'name'       => $contact->first_name,
                        'email'      => $contact->email,
                        'state'      => $contact->state,
                        'reason'     => json_encode($response),
                        'message'    => json_encode($message), // :white_check_mark: Encode it before saving
                    ]
                );
                $reserveContact = ReserveContact::where('email', $contact->email)->first();
                $contactData    = createContactData($contact, $agent, $campaign, true, true, $leadTypeId);
                if ($reserveContact) {
                    foreach ($contactData as $key => $value) {
                        $reserveContact->$key = $value;
                    }
                    $reserveContact->status = 'Not Sent';
                    $reserveContact->reason = 'Contact Not submitted or Sent Due to this reason ' . json_encode($response);
                    $reserveContact->save();
                    \Log::info("Updated ReserveContact  After Try with contact ID: {$contactId}");
                } else {
                    $reserveContact = new ReserveContact();
                    foreach ($contactData as $key => $value) {
                        $reserveContact->$key = $value;
                    }
                    $reserveContact->status = 'Not Sent';
                    $reserveContact->reason = 'Contact Not submitted or Sent Due to this reason ' . json_encode($response);
                    $reserveContact->save();
                    \Log::info("Created new ReserverContact After Try  with contact ID: {$contactId}");
                }
                $reserveContact = ReserveContact::where('email', $contact->email)->first();
                if ($reserveContact) {
                    $delprocesscon  = ProccessContact::where('contact_id', $contact->contact_id)->delete();
                    $delmaincontact = Contact::where('contact_id', $contact->contact_id)->delete();

                }
            }
            return $response;
        } else {
            // Create error response object
            $elseresponse         = new \stdClass();
            $elseresponse->status = 'error';
            $elseresponse->reason = 'Agent is not saved as user';

            \Log::error('Agent not found in users', [
                'agent_id' => $agent->id ?? 'unknown',
                'error'    => $elseresponse->reason,
            ]);
            return $elseresponse;
        }
    }
    protected function customFields($customData, $agent)
    {
        $noMatch    = [];
        $customData = json_decode(base64_decode($customData), true);
        if (! is_array($customData)) {
            throw new \Exception("Invalid custom data format.");
        }

        $location_id = $customData['location']['id'] ?? null;
        $user        = User::where('agent_id', $agent->id ?? '')->first();

        $locationId  = $user ? $user->location_id : null;
        $location_id = $agent->destination_location ?? $customData['location']['id'] ?? null;

        $customFields = CustomField::select('cf_name', 'cf_id', 'cf_key')
            ->where('location_id', $location_id)
            ->get();

        $customFieldsMap = $customFields->pluck('cf_key', 'cf_name')->mapWithKeys(function ($value, $key) {
            return [
                preg_replace('/\s+/', ' ', trim($key)) => preg_replace('/\s+/', ' ', trim($value)),
            ];
        })->toArray();

        $customFieldData = [];

        foreach ($customData as $key => $value) {
            $key = trim($key);

            if ($key === 'contact_id') {
                break;
            }

            if (array_key_exists($key, $customFieldsMap)) {
                $cfKey    = $customFieldsMap[$key];
                $cfRecord = $customFields->firstWhere('cf_key', $cfKey);
                $cfId     = $cfRecord ? $cfRecord->cf_id : null;
                $meta     = is_array($value) && isset($value['meta']) ? (object) $value['meta'] : null;

                if ($meta) {
                    $value['meta']     = $meta;
                    $customFieldData[] = (object) [
                        'id'          => $cfId,
                        'key'         => str_replace('contact.', '', $cfKey),
                        'field_value' => is_array($value) ? (object) $value : [$value],
                    ];
                } elseif (strpos(strtolower($key), 'pdf file') !== false || strpos(strtolower($key), 'selected plan image') !== false) {
                    $customFieldData[] = (object) [
                        'id'          => $cfId,
                        'key'         => str_replace('contact.', '', $cfKey),
                        'field_value' => is_array($value) ? $value[0] : $value,
                    ];
                } else {
                    if (! is_null($cfId)) {
                        $customFieldData[] = (object) [
                            'id'          => $cfId,
                            'key'         => str_replace('contact.', '', $cfKey),
                            'field_value' => $value,
                        ];
                    }
                }
            } else {
                $noMatch[] = $key;

            }
        }
        \Log::info("No match for custom field from the above data Checking below:", ['noMatch' => $noMatch]);
        if (! empty($customData['customData'])) {
            foreach ($customData['customData'] as $cdkey => $value) {
                $cdkey = trim($cdkey);
                // if(strpos($cdkey, 'Spouse') !== false ){
                //     \Log::info("Spouse found: " . $cdkey  . " value: " . json_encode($value));
                // }
                if (! (array_key_exists($cdkey, $customFieldsMap))) {
                    continue;
                }
                $cfKey    = $customFieldsMap[$cdkey];
                $cfRecord = $customFields->firstWhere('cf_key', $cfKey);
                $cfId     = $cfRecord ? $cfRecord->cf_id : null;
                // if(strpos($cdkey, 'Spouse') !== false ){
                //     \Log::info("Spouse found: " . $cdkey  . " value: " . json_encode($value) . "  id: " . $cfId);
                // }
                if (is_null($cfId)) {
                    continue;
                }

                $existingField = collect($customFieldData)->firstWhere('id', $cfId);
                // if(strpos($cdkey, 'Spouse') !== false ){
                //     \Log::info("Spouse found: " . $cdkey  . " value: " . json_encode($value) . "  Existing : " . json_encode($existingField));
                // }
                if ($existingField) {
                    if (! is_null($existingField->field_value) && $existingField->field_value !== '') {
                        continue;
                    } else {
                        $meta = is_array($value) && isset($value['meta']) ? (object) $value['meta'] : null;

                        if ($meta) {
                            $value['meta']              = $meta;
                            $existingField->field_value = is_array($value) ? (object) $value : [$value];
                        } elseif (strpos(strtolower($cdkey), 'pdf file') !== false || strpos(strtolower($cdkey), 'selected plan image') !== false) {
                            $existingField->field_value = is_array($value) ? $value[0] : $value;
                        } else {
                            $existingField->field_value = $value;
                        }
                        continue;
                    }
                }

                $meta = is_array($value) && isset($value['meta']) ? (object) $value['meta'] : null;

                if ($meta) {
                    $value['meta']     = $meta;
                    $customFieldData[] = (object) [
                        'id'          => $cfId,
                        'key'         => str_replace('contact.', '', $cfKey),
                        'field_value' => is_array($value) ? (object) $value : [$value],
                    ];
                } elseif (strpos(strtolower($cdkey), 'pdf file') !== false || strpos(strtolower($cdkey), 'selected plan image') !== false) {
                    $customFieldData[] = (object) [
                        'id'          => $cfId,
                        'key'         => str_replace('contact.', '', $cfKey),
                        'field_value' => is_array($value) ? $value[0] : $value,
                    ];
                } else {
                    $customFieldData[] = (object) [
                        'id'          => $cfId,
                        'key'         => str_replace('contact.', '', $cfKey),
                        'field_value' => $value,
                    ];
                    // if(strpos($cdkey, 'Spouse') !== false ){
                    //     \Log::info("Spouse found cf dtaa: " . json_encode($customFieldData) );
                    // }
                }
            }
        }
        // \Log::info('Date of CF we Sent :  ' .json_encode($customFieldData) );
        return $customFieldData;
    }
    protected function connectLocationFirst($agent)
    {
        $token           = GhlAuth::where('user_id', $agent->user_id)->where('user_type','Company')->first();
        $connectResponse = \App\Helpers\CRM::connectLocation($token->user_id, $agent->agentLeadTypes->first()->destination_location, $token);
        //dd($locationId);
        if (isset($connectResponse->location_id)) {
            if ($connectResponse->statusCode == 400) {
                \Log::error('Bad Request: Invalid locationId or accessToken', [
                    'location_id' => $agent->agentLeadTypes->first()->destination_location,
                    'user_id'     => $token->user_id,
                    'response'    => $connectResponse,
                ]);
                return false;
            }
            $ghl = GhlAuth::where('location_id', $connectResponse->location_id)->where('user_id', $agent->user_id ?? '')->first();

            $apicall = \App\Helpers\CRM::crmV2($agent->user_id, 'customFields', 'get', '', [], false, $connectResponse->location_id, $ghl);
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
                    if (! $customField) {
                        $customField = new CustomField();
                    }
                    foreach ($customFieldData as $key => $value) {
                        $customField->$key = $value;
                    }
                    $customField->save();
                }
            }
            return $ghl;
        }
    }

    /**
     * Reserve contact for further processing if no agent is available.
     */
    protected function ReserveContact($data, $agent = null, $campaign, $reason = null, $leadTypeId)
    {
        if ($agent) {
            //find campaign agent using the campaign and agent
            $campagent = CampaignAgent::where('agent_id', $agent->id)->where('campaign_id', $campaign->id)->first();
            $campagent->decrement('agent_count_weightage', 1);
        }
        $type           = $data['customData'];
        $contact_id     = $data['contact_id'];
        $my_signature   = json_encode($data['I have reviewed my application information above, and here is my signature.'] ?? null);
        $reserveContact = ReserveContact::where('email', $data['email'] ?? '')->first();
        $contactData    = CreateContactData($data, $agent, $campaign, false, false, $leadTypeId);
        if ($reserveContact) {
            foreach ($contactData as $key => $value) {
                $reserveContact->$key = $value;
            }
            $reserveContact->status = 'Not Sent';
            $reserveContact->reason = $reason;
            $reserveContact->save();
            \Log::info("Updated ReserveContact with contact ID: {$contact_id}");
        } else {
            $reserveContact = new ReserveContact();
            foreach ($contactData as $key => $value) {
                $reserveContact->$key = $value;
            }
            $reserveContact->status = 'Not Sent';
            $reserveContact->reason = $reason;
            $reserveContact->save();
            \Log::info("Created new ReserveContact with contact ID: {$contact_id}");
        }
    }
}
