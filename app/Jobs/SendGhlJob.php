<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\contact;
use App\Models\ProcessContact;
use App\Models\ReserveContact;
use App\Models\GhlAuth;
class SendGhlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data, $agent;

    public function __construct($data, $agent)
    {
        $this->data = $data;
        $this->agent = $agent;

    }

    public function handle()
    {
        $agent=$this->agent;

        $customData =$this->data['contact_json'];
        //dd($customData);
        $newdata = [
            'locationId' => $agent->destination_location,
            'firstName' => $this->data['first_name'] ?? null,
            'lastName' => $this->data['last_name'] ?? null,
            'email' => $this->data['email'] ?? null,
            'phone' => $this->data['phone'] ?? null,
            'tags' => isset($this->data['tags']) ? explode(',', $this->data['tags']) : [], // Convert back to array
            'address1' => $this->data['address1'] ?? null,
            'city' => $this->data['city'] ?? null,
            'state' => $this->data['state'] ?? null,
            'postalCode' => $this->data['postal_code'] ?? null,
            'country' => $this->data['country'] ?? null,
            'dateOfBirth' => $this->data['date_of_birth'] ?? null,

        ];
            $custom_field = \customFields($customData , $agent);
            $newdata['customFields'] = $custom_field;
            $url = 'contacts/upsert';
            $contactId = (int) $this->data['id'];
            //\Log::info(["Converted ID" => $contactId]);
            $contact = Contact::where('id', $contactId)->first();
            //\Log::info(["contact " => $contact]);
            $locationId = User::where('agent_id', $agent->id ?? '')->first();
           // \Log::info('Location for the Agent Id '. ($agent->id ?? ''));

            if($locationId) {
                $token = GhlAuth::where('location_id', $locationId->location_id)->first();
                // \Log::info(json_encode($token));
                $response = \App\Helpers\CRM::crmV2($locationId->id, $url, 'POST', $newdata, [], false, $locationId->location_id, $token);
                \Log::info('response.', ['url' => $url, 'response' => $response]);
                if($response && property_exists($response,'contact')){
                    $contact->status = 'Sent';
                    $contact->save();
                    $agent->increment('agent_count_weightage', 1);
                }else{
                    \Log::info('Conact Not submitted or Sent Due to thhis reason '. json_encode($response));
                    \Log::info('This contact is delete  from the Contact and  process table sent to resever contact ');
                     $reserveContact = ReserveContact::where('email', $contact->email)->first();
                      if ($reserveContact) {
                          foreach ($this->data as $key => $value) {
                              $reserveContact->$key = $value;
                          }
                          $reserveContact->status = 'Not Sent';
                          $reserveContact->save();
                          \Log::info("Updated ReserveContact with contact ID: {$contact_id}");
                        }else {
                          $reserveContact = new ReserveContact();
                          foreach ($this->data as $key => $value) {
                              $reserveContact->$key = $value;
                          }
                          $reserveContact->status = 'Not Sent';
                          $reserveContact->save();
                          \Log::info("Created new ReserverContact with contact ID: {$contact_id}");
                         }
                        $reserveContact = ReserveContact::where('email', $contact->email)->first();
                        if($reserveContact){
                            $contact=ProcessContact::where('contact_id',$contact->id)->delete();
                            $contact =Contact::where('contact_id', $contact->id)->delete();

                        }
                }
                return $response;
            } else {
                // Create error response object
                $response = new \stdClass();
                $response->status = 'error';
                $response->reason = 'Agent is not saved as user';

                \Log::error('Agent not found in users', [
                    'agent_id' => $agent->id ?? 'unknown',
                    'error' => $response->reason
                ]);

                return $response;
            }

    }
}
