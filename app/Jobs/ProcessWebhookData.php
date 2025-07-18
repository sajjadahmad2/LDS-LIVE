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
use Illuminate\Support\Facades\Log;

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
        $webhookdata  = $this->webhookdata;
        $location_id  = $webhookdata['location']['id'] ?? null;
        $campaign_id  = $this->campaign_id;
        $contact_id   = $webhookdata['contact_id'] ?? null;
        $mainCampaign = Campaign::find($campaign_id);
        $agentIds     = CampaignAgent::where('campaign_id', $campaign_id)->pluck('agent_id')->toArray();
        if (count($agentIds) === 0 || empty($mainCampaign) || (! isset($webhookdata['state']) && (! isset($webhookdata['contact_id']) || is_null($contact_id)))) {
            if (! is_null($contact_id)) {
                // Log::info(match (true) {
                //     count($agentIds) === 0        => 'No Agent Found or Limit Reached, Sent to Reserve',
                //     empty($mainCampaign)          => 'Campaign Not Provided, Sent to Reserve',
                //     ! isset($webhookdata['state']) => 'State Not Provided, Sent to Reserve',
                // });
                appendJobLog($contact_id, 'Contact send to the Reserve due to Campaign or stae is not provided');
               $this->ReserveContact($webhookdata, null, $mainCampaign,'Contact send to the Reserve due to Campaign or state is not provided');
            }
            return match (true) {
                count($agentIds) === 0 => null,
                empty($mainCampaign)          => response()->json(['error' => 'Campaign not found'], 404),
                ! isset($webhookdata['state']) => response()->json(['error' => 'State not provided'], 400),
            };
        }
        $state      = strtolower(trim($webhookdata['state'] ?? null));
        $sourceUser = User::where('location_id', $location_id)->first();

        if (! $sourceUser) {
            $sourceUser = User::where('location_id', 'Xi9xlFZVPhtek5GOUwrS')->first();


        }
        // \Log::info(["Contact Origniated From That Source having id" => $sourceUser->id ?? null]);
        $sourceUserId = $sourceUser->id;
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
        // first check the contact if exist in the process Contact or not with agent
        $email = $webhookdata['email'] ?? null;

        $searchContactByEmail = Contact::where(function ($query) use ($email, $contact_id) {
            if ($email) {
                $query->where('email', $email)
                      ->orWhere('contact_id', $contact_id);
            } else {
                $query->where('contact_id', $contact_id);
            }
        })
        ->where('status', 'SENT')
        ->first();
        if($searchContactByEmail){
            \Log::info("Contact Duplication Alert Please check the email : {$email}");
            appendJobLog($contact_id, 'Contact sent to reserve it already exist in the contact table and sent to the agent id : '.$searchContactByEmail->agent_id ?? null);
            $this->ReserveContact($webhookdata, null, $mainCampaign,'Contact sent to reserve it already exist in the contact table and sent to the agent id : '.$searchContactByEmail->agent_id ?? null);
            return;
        }
        $proccessContact = ProccessContact::where(function ($query) use ($email, $contact_id) {
                if ($email) {
                    $query->where('email', $email)
                          ->orWhere('contact_id', $contact_id);
                } else {
                    $query->where('contact_id', $contact_id);
                }
            })
            ->whereNotNull('agent_id')
            ->first();


        if (!$proccessContact) {
            // \Log::info('No Contact Found, Find the Best Agent again for contact id  ' . $contact_id);
            appendJobLog($contact_id, 'No Contact Found in Process table, Find the Best Agent again for contact id  ' . $contact_id);
            //Agents Where state and the campaighn matches
            $agents = Agent::whereHas('states', function ($query) use ($state) {
                $query->where(DB::raw('TRIM(LOWER(state))'), $state)
                    ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
            })
                ->whereIn('id', $agentIds)
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
                ->orderByDesc('weightage')   // Within priority, sort by highest weightage
                ->get();

            if ($agents->isEmpty()) {
                if (! is_null($contact_id)) {
                    // Log::info('No Agent Found or Limit Reached, Sent to Reserve');
                    appendJobLog($contact_id, 'No Agent Found or Limit Reached, Sent to Reserve ' . $contact_id);
                    $this->ReserveContact($webhookdata, null, $mainCampaign,'No Agent Found or Limit Reached, Sent to Reserve ');

                }
                return;
            }

            // Group agents by priority
            $groupedAgents     = $agents->groupBy('priority');
            $agentIdss = $groupedAgents->map(function ($group) {
                return $group->pluck('id')->toArray();
            });

            // \Log::info('State Agents After Submission of Contact ID'.$contact_id.': ' . json_encode($agentIdss));
            $dailyLimitReached = false;

            foreach ($groupedAgents as $priority => $priorityAgents) {
                $weightageFull = true; // Assume weightage is full for this priority group

                foreach ($priorityAgents as $agent) {
                    // Check if agent is within their limits
                    $total   = $agent->total_contacts_count < $agent->total_limit;
                    $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                    $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                    if ($total && $monthly && $daily) {
                        if ($agent->agent_count_weightage < $agent->weightage) {
                            // Assign lead and increment weightage
                            // Log::info('Agent found. Contact dispatched. Agent ID: ' . ($agent->name ?? ''));
                            appendJobLog($contact_id, 'Contact dispatched. Agent ID: ' . ($agent->name ?? ''));
                            $this->ProccessContact($webhookdata, $agent, $mainCampaign);
                            $agent->increment('agent_count_weightage', 1);
                            $weightageFull = false;
                            return; // Exit after assigning one lead
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
                            appendJobLog($contact_id, 'Weightage reset. Assigning contact to agent ID: ' . ($agent->name ?? ''));
                            $this->ProccessContact($webhookdata, $agent, $mainCampaign);
                            $agent->increment('agent_count_weightage', 1);
                            return;
                        }
                    }
                }
            }

            // If all agents in all priority levels are full, send to reserve
            // Log::info('All Agents weightage is full and limits reached. Sending to reserve.');
            appendJobLog($contact_id, 'All Agents weightage is full and limits reached. Sending to reserve ' . $contact_id);
            $this->ReserveContact($webhookdata, null, $mainCampaign,'All Agents weightage is full and limits reached. Sending to reserve');


        } else {
            // Process contact and assign it to the agent
            $selectedAgent = Agent::where('id', $proccessContact->agent_id)->first();
            // \Log::info('Agent found Contact is dispatched to the process JOB Agent ID: ' . $selectedAgent->name ?? '');
            appendJobLog($contact_id, 'Contact dispatched to the process JOB Agent ID: ' . $selectedAgent->name ?? '');
            $this->ProccessContact($webhookdata, $selectedAgent, $mainCampaign);
        }

    }

    protected function ProccessContact($webhookdata, $agent, $campaign)
    {
        $contact_id   = $webhookdata['contact_id'] ?? null;
        $my_signature = json_encode($webhookdata['I have reviewed my application information above, and here is my signature.'] ?? null);

        if (! $contact_id) {
            Log::error('Missing contact ID in data.');
            return;
        }

        $proccessContact = ProccessContact::where('email', $webhookdata['email'])->first();
        $contactData     = CreateContactData($webhookdata, $agent, $campaign, false);
        if ($proccessContact) {
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->save();
            // \Log::info("Updated  old Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
            appendJobLog($contact_id, "Updated  old Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
            $this->ContactProcess($proccessContact, $agent, $campaign);
        } else {
            $proccessContact = new ProccessContact();
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->save();
            // \Log::info("Created new Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
            appendJobLog($contact_id, "Created new Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
            $this->ContactProcess($proccessContact, $agent, $campaign);

        }
    }

    protected function ContactProcess($dbContact, $agent, $campaign)
    {
        $contact_id   = $dbContact['contact_id'] ?? null;
        $dbContact    = $dbContact ?? [];
        $my_signature = json_encode($this->data['I have reviewed my application information above, and here is my signature.'] ?? null);

        if (! $contact_id) {
            Log::error('Missing contact ID in data.');
            return;
        }
        // Attempt to find an existing contact based on contact_id, email, or phone
        $contact = Contact::where(function ($query) use ($contact_id, $dbContact) {
            if (! empty($dbContact['email'])) {
                $query->orWhere('email', $dbContact['email']);
            }

            if (! empty($dbContact['phone'])) {
                $query->orWhere('phone', $dbContact['phone']);
            }

            // Uncomment if you want to include contact_id in the search
            // if (!empty($contact_id)) {
            //     $query->orWhere('contact_id', $contact_id);
            // }
        })->first();

        // Prepare contact data
        $contactData = createContactData($dbContact, $agent, $campaign, true, true);

        if ($contact) {
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
            // \Log::info("Updated old in contact table with contact ID: {$contact_id} and state is : {$contact->state} With status NOT SENT ");
            appendJobLog($contact_id, "Updated old in contact table with contact ID: {$contact_id} and state is : {$contact->state} With status NOT SENT ");

        } else {
            $contact = new Contact();
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
            // \Log::info("Created new Contact in contact table  with contact ID: {$contact_id} and state is : {$contact->state} With status NOT SENT ");
            appendJobLog($contact_id, "Created new Contact in contact table  with contact ID: {$contact_id} and state is : {$contact->state} With status NOT SENT ");

            //$this->SendGhl($contact, $agent, $campaign);

        }
        $findContact = Contact::where('status', 'NOT SENT')
            ->where(function ($query) use ($contact_id, $dbContact) {
                if (! empty($dbContact['email'])) {
                    $query->orWhere('email', $dbContact['email']);
                }
                if (! empty($dbContact['phone'])) {
                    $query->orWhere('phone', $dbContact['phone']);
                }
                // Uncomment if needed
                // if (!empty($contact_id)) {
                //     $query->orWhere('contact_id', $contact_id);
                // }
            })
            ->first();
        if ($findContact) {
            // \Log::info("Updated old in contact table with contact ID: {$contact_id} and state is : {$findContact->state} With status{$findContact->status} Forward to GHL");
            appendJobLog($contact_id, "Updated old in contact table with contact ID: {$contact_id} and state is : {$findContact->state} With status{$findContact->status} Forward to GHL");
            $this->SendGhl($findContact, $agent, $campaign);
        } else {
            // \Log::info(" Contact in contact table  with contact ID: {$contact_id} With status  SENT not forward to GHL ");
            appendJobLog($contact_id, " Contact in contact table  with contact ID: {$contact_id} With status  SENT and sent to that agent : {$findContact->agent_id} not forward to GHL ");
        }
    }

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
        appendJobLog($contact->contact_id,'Destination location of  the Agent Id ' . $agentUser->id ?? '' . ' ' . $agentUser->location_id ?? '');
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
            appendJobLog($contact->contact_id,'This ApiCall made by  this agent having id : ' . $agentUser->id . 'and the location id: ' . $agentUser->location_id ?? '');
            $response = \App\Helpers\CRM::crmV2($agentUser->id, $url, 'POST', $newdata, [], false, $agentUser->location_id, $token);
            \Log::info('response.', ['url' => $url, 'response' => $response]);
            if ($response && property_exists($response, 'contact')) {
                $contact->status = 'Sent';
                $contact->save();
                appendJobLog($contact->contact_id,'Contact sent Successfully to that agent  : ' . $agentUser->agent_id . 'and the location id: ' . $agentUser->location_id ?? '');

            } else {
                $agent->decrement('agent_count_weightage', 1);
                $contactId = $contact->id;
                $contact   = Contact::where('id', $contactId)->first();
                \Log::info('Contact Not submitted or Sent Due to this reason ' . json_encode($response));
                \Log::info('This contact is delete  from the Contact and  process table sent to resever contact ' . $contactId);
                appendJobLog($contact->contact_id,'Contact Not submitted or Sent Due to this reason ' . json_encode($response));
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
                    $reserveContact->reason = 'Contact Not submitted or Sent Due to this reason ' . json_encode($response);
                    $reserveContact->status = 'Not Sent';
                    $reserveContact->save();
                    \Log::info("Updated ReserveContact  After Try with contact ID: {$contactId}");
                } else {
                    $reserveContact = new ReserveContact();
                    foreach ($contactData as $key => $value) {
                        $reserveContact->$key = $value;
                    }
                    $reserveContact->reason = 'Contact Not submitted or Sent Due to this reason ' . json_encode($response);
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
    $noMatch=[];
    $customData = json_decode(base64_decode($customData), true);
    if (!is_array($customData)) {
        throw new \Exception("Invalid custom data format.");
    }

    $location_id = $customData['location']['id'] ?? null;
    $user = User::where('agent_id', $agent->id ?? '')->first();

    $locationId = $user ? $user->location_id : null;
    $location_id = $agent->destination_location ?? $customData['location']['id'] ?? null;

    $customFields = CustomField::select('cf_name', 'cf_id', 'cf_key')
        ->where('location_id', $location_id)
        ->get();

        $customFieldsMap = $customFields->pluck('cf_key', 'cf_name')->mapWithKeys(function ($value, $key) {
            return [
                preg_replace('/\s+/', ' ', trim($key)) => preg_replace('/\s+/', ' ', trim($value))
            ];
        })->toArray();


    $customFieldData = [];

    foreach ($customData as $key => $value) {
        $key = trim($key);

        if ($key === 'contact_id') {
            break;
        }

        if (array_key_exists($key, $customFieldsMap)) {
            $cfKey = $customFieldsMap[$key];
            $cfRecord = $customFields->firstWhere('cf_key', $cfKey);
            $cfId = $cfRecord ? $cfRecord->cf_id : null;
            $meta = is_array($value) && isset($value['meta']) ? (object) $value['meta'] : null;

            if ($meta) {
                $value['meta'] = $meta;
                $customFieldData[] = (object) [
                    'id' => $cfId,
                    'key' => str_replace('contact.', '', $cfKey),
                    'field_value' => is_array($value) ? (object) $value : [$value],
                ];
            } elseif (strpos(strtolower($key), 'pdf file') !== false || strpos(strtolower($key), 'selected plan image') !== false) {
                $customFieldData[] = (object) [
                    'id' => $cfId,
                    'key' => str_replace('contact.', '', $cfKey),
                    'field_value' => is_array($value) ? $value[0] : $value,
                ];
            } else {
                if(!is_null( $cfId)){
                    $customFieldData[] = (object) [
                        'id' => $cfId,
                        'key' => str_replace('contact.', '', $cfKey),
                        'field_value' => $value,
                    ];
                }
            }
        } else {
            $noMatch[] = $key;

        }
    }
    \Log::info("No match for custom field from the above data Checking below:", ['noMatch' => $noMatch]);
    if (!empty($customData['customData'])) {
        foreach ($customData['customData'] as $cdkey => $value) {
            $cdkey = trim($cdkey);
            // if(strpos($cdkey, 'Spouse') !== false ){
            //     \Log::info("Spouse found: " . $cdkey  . " value: " . json_encode($value));
            // }
            if (!(array_key_exists($cdkey, $customFieldsMap))) {
                continue;
            }
            $cfKey = $customFieldsMap[$cdkey];
            $cfRecord = $customFields->firstWhere('cf_key', $cfKey);
            $cfId = $cfRecord ? $cfRecord->cf_id : null;
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
                if (!is_null($existingField->field_value) && $existingField->field_value !== '') {
                    continue;
                } else {
                    $meta = is_array($value) && isset($value['meta']) ? (object) $value['meta'] : null;

                    if ($meta) {
                        $value['meta'] = $meta;
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
                $value['meta'] = $meta;
                $customFieldData[] = (object) [
                    'id' => $cfId,
                    'key' => str_replace('contact.', '', $cfKey),
                    'field_value' => is_array($value) ? (object) $value : [$value],
                ];
            } elseif (strpos(strtolower($cdkey), 'pdf file') !== false || strpos(strtolower($cdkey), 'selected plan image') !== false) {
                $customFieldData[] = (object) [
                    'id' => $cfId,
                    'key' => str_replace('contact.', '', $cfKey),
                    'field_value' => is_array($value) ? $value[0] : $value,
                ];
            } else {
                $customFieldData[] = (object) [
                    'id' => $cfId,
                    'key' => str_replace('contact.', '', $cfKey),
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
    protected function ReserveContact($data, $agent = null, $campaign,$reason = null)
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
// $agents = Agent::whereHas('states', function ($query) use ($state) {
//     $query->where(DB::raw('TRIM(LOWER(state))'), $state)
//           ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
// })
// ->whereIn('id', $agentIds)
// ->withCount([
//     'contacts as monthly_contacts_count' => function ($query) use ($currentMonth) {
//         $query->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
//     },
//     'contacts as daily_contacts_count' => function ($query) use ($currentDate) {
//         $query->where('status', 'Sent')->whereDate('created_at', $currentDate);
//     },
//     'contacts as total_contacts_count' => function ($query) {
//         $query->where('status', 'Sent');
//     },
// ])
// ->orderBy('priority', 'asc') // Sort by priority first (smallest value = highest priority)
// ->orderByDesc('weightage')    // If same priority, sort by highest weightage
// ->get();

// if ($agents->isEmpty()) {
// if (!is_null($contact_id)) {
//     Log::info('No Agent Found or Limit Reached, Sent to Reserve');
//     $this->ReserveContact($webhookdata, null, $mainCampaign);
// }
// return;
// }

// $filteredAgents = [];
// $weightagefull = true;

// foreach ($agents as $agent) {
// $total = $agent->total_contacts_count < $agent->total_limit;
// $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
// $daily = $agent->daily_contacts_count < $agent->daily_limit;

// if ($total && $monthly && $daily) {
//     $filteredAgents[] = $agent;

//     if ($agent->agent_count_weightage < $agent->weightage) {
//         Log::info('Agent found. Contact dispatched. Agent ID: ' . ($agent->name ?? ''));
//         $this->ProccessContact($webhookdata, $agent, $mainCampaign);
//         $agent->increment('agent_count_weightage', 1); // Increment weightage count
//         $weightagefull = false;
//         break;
//     }
// }
// }

// // Step 4: If all agents have hit their weightage limit, reset and select the best one
// if ($weightagefull && count($filteredAgents) > 0) {
// $agentsCollection = collect($filteredAgents);

// // Reset agent_count_weightage for all filtered agents
// $agentsCollection->each(function ($agent) {
//     $agent->update(['agent_count_weightage' => 0]);
// });

// // Find the highest priority (smallest priority number)
// $minPriority = $agentsCollection->min('priority');

// // Get agents with the highest priority
// $topAgents = $agentsCollection->where('priority', $minPriority);

// // Select the agent with the highest weightage among them
// $topAgent = $topAgents->sortByDesc('weightage')->first();

// if ($topAgent) {
//     Log::info('All Agents weightage is full. Assigning to highest priority and weightage agent: ' . $topAgent->id);
//     $this->ProccessContact($webhookdata, $topAgent, $mainCampaign);
//     $topAgent->increment('agent_count_weightage', 1);
// }
// }

// // Step 5: If all agents are full and limits are reached, send to reserve
// if ($weightagefull && count($filteredAgents) == 0) {
// Log::info('All Agents weightage is full and limits reached. Sending to reserve.');
// $this->ReserveContact($webhookdata, null, $mainCampaign);
// }
