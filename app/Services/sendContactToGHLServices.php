<?php
namespace App\Services;
use App\Models\User;
use App\Models\GhlAuth;
class sendContactToGHLServices{
    public function sendContactToGHL(array $data , $agent){
    //dd($data , $agent);
    $contact=$data;
    $customData =$data['contact_json'];
    //dd($customData);
    $data = [
        'locationId' => $agent->destination_location,
        'firstName' => $data['first_name'] ?? null,
        'lastName' => $data['last_name'] ?? null,
        'email' => $data['email'] ?? null,
        'phone' => $data['phone'] ?? null,
        'tags' => isset($data['tags']) ? explode(',', $data['tags']) : [], // Convert back to array
        'address1' => $data['address1'] ?? null,
        'city' => $data['city'] ?? null,
        'state' => $data['state'] ?? null,
        'postalCode' => $data['postal_code'] ?? null,
        'country' => $data['country'] ?? null,
        'dateOfBirth' => $data['date_of_birth'] ?? null,

    ];
        $custom_field = \customFields($customData , $agent);
        $data['customFields'] = $custom_field;
        $url = 'contacts/upsert';
        
        $locationId = User::where('agent_id', $agent->id ?? '')->first();
        \Log::info('Location for the Agent Id '. ($agent->id ?? ''));
        
        if($locationId) {
            $token = GhlAuth::where('location_id', $locationId->location_id)->first();
            // \Log::info(json_encode($token));
            $response = \App\Helpers\CRM::crmV2($locationId->id, $url, 'POST', $data, [], false, $locationId->location_id, $token);
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
                      foreach ($contact as $key => $value) {
                          $reserveContact->$key = $value;
                      }
                      $reserveContact->status = 'Not Sent';
                      $reserveContact->save();
                      \Log::info("Updated ReserveContact with contact ID: {$contact_id}");
                    }else {
                      $reserveContact = new ReserveContact();
                      foreach ($contact as $key => $value) {
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
