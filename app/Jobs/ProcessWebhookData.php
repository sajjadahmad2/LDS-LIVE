<?php
namespace App\Jobs;

use App\Models\Agent;
use App\Models\Campaign;
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
        $webhookdata = $this->webhookdata;
        $campaign_id = $this->campaign_id;
        $contact_id  = $webhookdata['contact_id'] ?? null;
        $email       = $webhookdata['email'] ?? null;
        $state       = $webhookdata['state'] ?? null;
        // Fetch campaign and agent details
        $mainCampaign = Campaign::find($campaign_id);
        $agentIds     = Agent::whereHas('states', function ($query) use ($state) {
            $query->where(DB::raw('TRIM(LOWER(state))'), strtolower(trim($state)))
                ->orWhere(DB::raw('TRIM(LOWER(short_form))'), strtolower(trim($state)));
        })
            ->whereIn('id', function ($q) use ($campaign_id) {
                $q->select('agent_id')
                    ->from('campaign_agents')
                    ->where('campaign_id', $campaign_id);
            })
            ->pluck('id')
            ->toArray();

        if (! $this->validateInitialConditions($webhookdata, $contact_id, $mainCampaign, $agentIds)) {
            return;
        }

        // Check for duplicates
        $duplicateContact = $this->checkForDuplicateContact($email, $contact_id);
        if ($duplicateContact) {
            // If duplicate found, assign to another agent
            $this->assignLeadToAnotherAgent($duplicateContact, $email, $contact_id, $mainCampaign, $agentIds,$webhookdata);
            return; // Stop further processing as duplicate has been handled
        }

        // Proceed with normal contact assignment if no duplicate is found
        $proccessContact = $this->getProccessContact($email, $contact_id);
        $this->assignLeadToAgent($proccessContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata);
    }

    /**
     * Validate initial conditions such as missing data or agent/campaign info.
     */
    private function validateInitialConditions($webhookdata, $contact_id, $mainCampaign, $agentIds)
    {
        if (count($agentIds) === 0 || empty($mainCampaign) || (! isset($webhookdata['state']) && (! isset($contact_id) || is_null($contact_id)))) {
            appendJobLog($contact_id, 'Missing agent, campaign or state, contact sent to reserve.');
            $this->ReserveContact($webhookdata, null, $mainCampaign, 'Missing agent, campaign, or state.');
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
    private function assignLeadToAnotherAgent($duplicateContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata)
    {
        // Get the agent ID who was originally assigned to this contact
        $originalAgentId = $duplicateContact->agent_id;
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
        // Fetch all eligible agents excluding the original one
        $agents = Agent::whereIn('id', $agentIds)
            ->where('id', '!=', $originalAgentId)
            ->withCount([
                'contacts as monthly_contacts_count' => function ($query) use ($currentMonth) {
                    $query->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                },
                'contacts as daily_contacts_count'   => function ($query) use ($currentDate) {
                    $query->where('status', 'Sent')->whereDate('created_at', $currentDate);
                },
                'contacts as total_contacts_count'   => function ($query) {
                    $query->where('status', 'Sent');
                },
            ])
            ->orderBy('priority', 'asc') // Sort by highest priority (lowest value)
            ->orderByDesc('weightage')   // Exclude the original agent
            ->get();

        if ($agents->isEmpty()) {
            appendJobLog($contact_id, 'No available agents other than the original one. Sent to reserve.');
            $this->ReserveContact($webhookdata, null, $mainCampaign, 'No available agents for that state other than the original one.');
            return;
        }

        // Assign the lead to the first available agent
        $this->assignLeadToBestAgent($agents, $contact_id, $email, $webhookdata, $mainCampaign);
    }

    /**
     * Assign lead to the best available agent based on their limits.
     */
    private function assignLeadToBestAgent($agents, $contact_id, $email, $webhookdata, $mainCampaign)
    {
        $agents            = $agents->groupBy('priority');
        $dailyLimitReached = false;
        foreach ($agents as $priority => $priorityAgents) {
            $agent=$this->isAgentAvailable($priority, $priorityAgents);
            if ($agent) {
                appendJobLog($contact_id, 'Lead assigned to new agent: ' . ($agent->name ?? 'Unknown'));
                $this->ProccessContact($webhookdata, $agent, $mainCampaign);
                return;
            }
        }

        appendJobLog($contact_id, 'All agents are at full capacity. Sent to reserve.');
        $this->ReserveContact($webhookdata, null, $mainCampaign, 'All agents full, lead sent to reserve.');
    }

    /**
     * Check if the agent is within their contact limits.
     */
    private function isAgentAvailable($priority, $priorityAgents)
    {
        $weightageFull = true; // Assume weightage is full for this priority group

        foreach ($priorityAgents as $agent) {
            // Check if agent is within their limits
            $total   = $agent->total_contacts_count < $agent->total_limit;
            $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
            $daily   = $agent->daily_contacts_count < $agent->daily_limit;

            if ($total && $monthly && $daily) {
                if ($agent->agent_count_weightage < $agent->weightage) {
                    $weightageFull = false;
                    return $agent; // Exit after assigning one lead
                }
            }
        }

        // If all agents in this priority level have full weightage, reset and retry
        if ($weightageFull) {
            foreach ($priorityAgents as $agent) {
                // Check limits again before resetting weightage
                $total   = $agent->total_contacts_count < $agent->total_limit;
                $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                if ($total && $monthly && $daily) {
                    $agent->update(['agent_count_weightage' => 0]);
                }
            }

            // Retry with the same priority level after weightage reset
            foreach ($priorityAgents as $agent) {
                $total   = $agent->total_contacts_count < $agent->total_limit;
                $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                if ($total && $monthly && $daily) {
                    // Log::info('Weightage reset. Assigning contact to agent ID: ' . ($agent->name ?? ''));
                    $agent->increment('agent_count_weightage', 1);
                    return $agent;

                }
            }
        }

        return false;
    }

    /**
     * Assign lead to an agent if no duplicate is found.
     */
    private function assignLeadToAgent($proccessContact, $email, $contact_id, $mainCampaign, $agentIds, $webhookdata)
    {
        if (! $proccessContact) {
            appendJobLog($contact_id, 'No contact found in ProccessContact table. Finding the best agent.');
            $agents = $this->getEligibleAgents($agentIds);
            if ($agents->isEmpty()) {
                appendJobLog($contact_id, 'No eligible agents found. Sent to reserve.');
                $this->ReserveContact($webhookdata, null, $mainCampaign, 'No eligible agents found.');
                return;
            }

            $this->assignLeadToBestAgent($agents, $contact_id, $email, $webhookdata, $mainCampaign);
        } else {
            // If the contact is found, assign it to the agent
            if($proccessContact->agent_id ){
                $agent=Agent::where('id',$proccessContact->agent_id)->first();
                appendJobLog($contact_id, 'Lead assigned to existing agent: ' . ($agent->name ?? 'Unknown'));
            }
            $this->ProccessContact($webhookdata, $agent, $mainCampaign);
        }
    }

    /**
     * Get eligible agents based on limits.
     */
    private function getEligibleAgents($agentIds)
    {
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');

        return Agent::whereIn('id', $agentIds)
            ->withCount([
                'contacts as monthly_contacts_count' => function ($query) use ($currentMonth) {
                    $query->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                },
                'contacts as daily_contacts_count'   => function ($query) use ($currentDate) {
                    $query->where('status', 'Sent')->whereDate('created_at', $currentDate);
                },
                'contacts as total_contacts_count'   => function ($query) {
                    $query->where('status', 'Sent');
                },
            ])
            ->orderBy('priority', 'asc')
            ->orderByDesc('weightage')
            ->get();
    }

    /**
     * Process the contact and assign it to the agent.
     */
    protected function ProccessContact($webhookdata, $agent, $campaign)
    {
        $contact_id      = $webhookdata['contact_id'] ?? null;
        $my_signature    = json_encode($webhookdata['I have reviewed my application information above, and here is my signature.'] ?? null);
        $proccessContact = ProccessContact::where('contact_id', $contact_id)->first();
        $contactData     = CreateContactData($webhookdata, $agent, $campaign, false);
        if (! $proccessContact) {
            $proccessContact = new ProccessContact();
        }
        foreach ($contactData as $key => $value) {
            $proccessContact->$key = $value;
        }
        $proccessContact->save();
        appendJobLog($contact_id, "Updated  Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
        appendJobLog($contact_id, "Contact processed for agent ID: " . ($agent->name ?? 'Unknown'));
        $this->ContactProcess($proccessContact, $agent, $campaign);
    }

    /**
     * Finalize the contact process and send it to GHL.
     */
    protected function ContactProcess($dbContact, $agent, $campaign)
    {
        $my_signature = json_encode($this->data['I have reviewed my application information above, and here is my signature.'] ?? null);
        $contact_id   = $dbContact['contact_id'] ?? null;
        $contactData  = createContactData($dbContact, $agent, $campaign, true, true);
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
        $this->SendGhl($contact, $agent, $campaign);
    }

    /**
     * Send contact data to GHL.
     */
    protected function SendGhl($contact, $agent, $campaign)
    {
        $customData = $contact->contact_json;
        $tags       = ! empty($contact->tags) ? explode(',', (string) $contact->tags) : [];
        $tags       = array_merge($tags, ['aca']);
        $newdata    = [
            'locationId'  => $agent->destination_location,
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
        $agentUser = User::where('agent_id', $agent->id ?? '')->first();
        \Log::info('Destination location of  the Agent Id ' . $agentUser->id ?? '' . ' ' . $agentUser->location_id ?? '');
        appendJobLog($contact->contact_id, 'Destination location of  the Agent Id ' . $agentUser->id ?? '' . ' ' . $agentUser->location_id ?? '');
        if ($agentUser) {
            $token = \App\Models\GhlAuth::where('location_id', $agentUser->location_id)->where('user_id', $agentUser->id ?? '')->first();
            if (! $token) {
                sleep(15);
                $token = $this->connectLocationFirst($agentUser);
            }
            $custom_field            = $this->customFields($customData, $agent);
            $newdata['customFields'] = $custom_field;
            $url                     = 'contacts';
            sleep(15);

            \Log::info('This ApiCall made by  this agent having id : ' . $agentUser->id . 'and the location id: ' . $agentUser->location_id ?? '');
            appendJobLog($contact->contact_id, 'This ApiCall made by  this agent having id : ' . $agentUser->id . 'and the location id: ' . $agentUser->location_id ?? '');
            $response = \App\Helpers\CRM::crmV2($agentUser->id, $url, 'POST', $newdata, [], false, $agentUser->location_id, $token);
            \Log::info('response.', ['url' => $url, 'response' => $response]);
            if ($response && property_exists($response, 'contact')) {
                $contact->status = 'Sent';
                $contact->save();
                appendJobLog($contact->contact_id, 'Contact sent Successfully to that agent  : ' . $agentUser->agent_id . 'and the location id: ' . $agentUser->location_id ?? '');

            } else {
                $agent->decrement('agent_count_weightage', 1);
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
                $contactData    = createContactData($contact, $agent, $campaign, true, true);
                if ($reserveContact) {
                    foreach ($contactData as $key => $value) {
                        $reserveContact->$key = $value;
                    }
                    $reserveContact->status = 'Not Sent';
                    $reserveContact->save();
                    \Log::info("Updated ReserveContact  After Try with contact ID: {$contactId}");
                } else {
                    $reserveContact = new ReserveContact();
                    foreach ($contactData as $key => $value) {
                        $reserveContact->$key = $value;
                    }
                    $reserveContact->status = 'Not Sent';
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
    protected function connectLocationFirst($agentUser)
    {
        $token           = GhlAuth::where('user_id', User::where('role', 0)->first()->id)->first();
        $connectResponse = \App\Helpers\CRM::connectLocation($token->user_id, $agentUser->location_id, $token, $agentUser->id);
        //dd($locationId);
        if (isset($connectResponse->location_id)) {
            if ($connectResponse->statusCode == 400) {
                \Log::error('Bad Request: Invalid locationId or accessToken', [
                    'location_id' => $agentUser->location_id,
                    'user_id'     => $token->user_id,
                    'response'    => $connectResponse,
                ]);
                return false;
            }
            $ghl = GhlAuth::where('location_id', $connectResponse->location_id)->where('user_id', $agentUser->id ?? '')->first();

            $apicall = \App\Helpers\CRM::crmV2($agentUser->id, 'customFields', 'get', '', [], false, $connectResponse->location_id, $ghl);
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
    protected function ReserveContact($data, $agent = null, $campaign, $reason = null)
    {
        $type           = $data['customData'];
        $contact_id     = $data['contact_id'];
        $my_signature   = json_encode($data['I have reviewed my application information above, and here is my signature.'] ?? null);
        $reserveContact = ReserveContact::where('email', $data['email'] ?? '')->first();
        $contactData    = CreateContactData($data, $agent, $campaign, false);
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
