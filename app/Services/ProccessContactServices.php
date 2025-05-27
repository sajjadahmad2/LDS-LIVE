<?php
namespace App\Services;

use App\Models\ProccessContact;
use App\Models\Contact;
use App\Models\ReserveContact;
use App\Models\GhlAuth;
use App\Models\User;
use App\Models\CustomField;
use App\Helpers\CRM;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProccessContactServices
{
    public function handleProccessContact(array $data, $agent, $campaign)
    {
       // dd($data, $agent, $campaign);
        $this->ProccessContact($data, $agent, $campaign);
    }

    protected function ProccessContact($data, $agent, $campaign)
    {
        $contact_id   = $data['contact_id'] ?? null;
        //dd($contact_id);
        $my_signature = json_encode($data['I have reviewed my application information above, and here is my signature.'] ?? null);

        if (! $contact_id) {
            Log::error('Missing contact ID in data.');
            return;
        }

        $proccessContact = ProccessContact::where('email', $data['email'])->first();

        $contactData     = CreateContactData($data, $agent, $campaign, false);

        //dd($contactData);
        if ($proccessContact) {
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->save();
            \Log::info("Updated  old Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
            $this->ContactProcess($proccessContact, $agent, $campaign);
        } else {
            $proccessContact = new ProccessContact();
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->save();
            \Log::info("Created new Contact in Process Contact Table with contact ID: {$contact_id} and state is : {$proccessContact->state} With status NOT SENT");
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
        $contact = Contact::where('status', 'NOT SENT')
            ->where(function ($query) use ($contact_id, $dbContact) {
                $query->where('contact_id', $contact_id)
                    ->orWhere('email', $dbContact['email'] ?? null)
                    ->orWhere('phone', $dbContact['phone'] ?? null);
            })
            ->first();

        // Prepare contact data
        $contactData = createContactData($dbContact, $agent, $campaign, true,true);

        if ($contact) {
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
            \Log::info("Updated old in contact table with contact ID: {$contact_id} and state is : {$contact->state} With status NOT SENT and forward to GHL");
            $this->SendGhl($contact, $agent, $campaign);
        } else {
            $contact = new Contact();
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
            \Log::info("Created new Contact in contact table  with contact ID: {$contact_id} and state is : {$contact->state} With status NOT SENT and forward to GHL");
            $this->SendGhl($contact, $agent, $campaign);

        }
    }

    protected function SendGhl($contact, $agent,$campaign)
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
        \Log::info('Destination location of  the Agent Id ' . $agentUser->id ?? '' . ' ' .$agentUser->location_id ?? '');
        if ($agentUser) {
            $token = \App\Models\GhlAuth::where('location_id', $agentUser->location_id)->where('user_id', $agentUser->id ?? '')->first();
            if (! $token) {
                sleep(5);
                $token=$this->connectLocationFirst($agentUser);
            }
            $custom_field            = \customFields($customData, $agent);
            //dd($custom_field);
            $newdata['customFields'] = $custom_field;
            $url                     = 'contacts/upsert';
            sleep(5);

            \Log::info('This ApiCall made by  this agent having id : '. $agentUser->id.'and the location id: '. $agentUser->location_id ?? '');
            $response = \App\Helpers\CRM::crmV2($agentUser->id, $url, 'POST', $newdata, [], false, $agentUser->location_id, $token);
             //dd($response);
            \Log::info('response.', ['url' => $url, 'response' => $response]);
            if ($response && property_exists($response, 'contact')) {
                $contact->status = 'Sent';
                $contact->save();
                $agent->increment('agent_count_weightage', 1);
                $delreservecon = ReserveContact::where('contact_id', $contact->contact_id)->delete();
            }
            return $response;
        } else {
            // Create error response object
            $response         = new \stdClass();
            $response->status = 'error';
            $response->reason = 'Agent is not saved as user';

            \Log::error('Agent not found in users', [
                'agent_id' => $agent->id ?? 'unknown',
                'error'    => $response->reason,
            ]);
            return $response;
        }
    }
    protected function connectLocationFirst($agentUser)
    {
        $token           = GhlAuth::where('user_id', User::where('role', 0)->first()->id)->first();
        $connectResponse =  \App\Helpers\CRM::connectLocation($token->user_id, $agentUser->location_id, $token,$agentUser->id);
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
            $ghl     = GhlAuth::where('location_id', $connectResponse->location_id)->where('user_id', $agentUser->id ?? '')->first();

            $apicall =  \App\Helpers\CRM::crmV2($agentUser->id, 'customFields', 'get', '', [], false, $connectResponse->location_id, $ghl);
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
                    if (!$customField) {
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
    protected function ReserveContact($data, $agent = null, $campaign)
    {
        $type           = $data['customData'];
        $contact_id     = $data['contact_id'];
        $my_signature   = json_encode($data['I have reviewed my application information above, and here is my signature.']);
        $reserveContact = ReserveContact::where('email', $data['email'])->first();
        $contactData    = CreateContactData($data, $agent, $campaign, false);
        if ($reserveContact) {
            foreach ($contactData as $key => $value) {
                $reserveContact->$key = $value;
            }
            $reserveContact->status = 'Not Sent';
            $reserveContact->save();
            \Log::info("Updated ReserveContact with contact ID: {$contact_id}");
        } else {
            $reserveContact = new ReserveContact();
            foreach ($contactData as $key => $value) {
                $reserveContact->$key = $value;
            }
            $reserveContact->status = 'Not Sent';
            $reserveContact->save();
            \Log::info("Created new ReserveContact with contact ID: {$contact_id}");
        }
    }
}
