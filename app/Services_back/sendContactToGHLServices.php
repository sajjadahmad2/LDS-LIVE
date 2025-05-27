<?php
namespace App\Services;
use App\Models\User;
use App\Models\GhlAuth;
class sendContactToGHLServices{
    public function sendContactToGHL(array $data , $agent){
        //dd($data , $agent);
    $customData =$data['contact_json'];
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
    \Log::info(json_encode($data));
    $locationId = User::where('agent_id',$agent->id ??'')->first();
    // dd($locationId);
   // dd($locationId->location_id);
    $token = GhlAuth::where('location_id', $locationId->location_id)->first();
//   dd($token);
    $response = \App\Helpers\CRM::crmV2($locationId->id, $url, 'POST', $data, [], false, $locationId->location_id, $token);
    \Log::info('response.', ['url' => $url, 'response' => $response]);
    return [ 'response' => $response];
    }
}
