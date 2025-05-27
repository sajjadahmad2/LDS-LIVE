<?php
namespace App\Services;
use App\Models\Contact;
use Carbon\Carbon;
use DB;
class ContactServices{
    public function handleContact(array $data)
    {
     $type = $data['type'] ?? null;
    $location_id = $data['locationId'] ?? $data['location_id'];
    $contact = Contact::where('ghl_contact_id', $data['id'])
        ->where('location_id', $location_id)
        ->first();
    if ($type === 'ContactDelete') {
        if ($contact) {
            $contact->delete();
            Log::info("Contact with GHL Contact ID: {$data['id']} deleted successfully and type is {$type}");
        } else {
            Log::info("Contact with GHL Contact ID: {$data['id']} not found and type is {$type}.");
        }
        return 'done';
    }

    $contactData = [
        'ghl_contact_id' => $data['id'],
        'location_id' => $location_id,
        'first_name' =>  $data['firstName'] ?? null,
        'last_name' => $data['lastName'] ?? null,
        'name' => $data['name'] ?? null,
        'email' => $data['email'] ?? null,
        'address1' => $type['address1'] ?? null,
        'city' => $type['city'] ?? null,
        'state' => $type['state'] ?? null,
        'company' => $data['companyName'] ?? null,
        'country' => $data['country'] ?? null,
        'source' => $data['source'] ?? null,
        'date_added' => isset($data['dateAdded']) ? Carbon::parse($data['dateAdded'])->format('Y-m-d H:i:s') : null,
        'date_of_birth' => isset($data['dateOfBirth']) ? date('Y-m-d', strtotime($data['dateOfBirth'])) : null,
        'dnd' => $data['dnd'] ?? null,
        'postal_code' => $type['postal_code'] ?? null,
        'city' => $data['city'] ?? null,
        'phone' => $data['phone'] ?? null,
        'postal_code' => $data['postalCode'] ?? null,
        'assigned_to' => $data['assignedTo'] ?? null,
        'tags' => isset($data['tags']) ? implode(',', $data['tags']) : null, // Convert tags array to a comma-separated string
        'attachments' => $data['attachments']?? null,

    ];
    if ($contact) {
        foreach ($contactData as $key => $value) {
            $contact->$key = $value;
        }
        $contact->save();
    } else {
        $contact = new Contact();
        foreach ($contactData as $key => $value) {
            $contact->$key = $value;
        }
       $contact->save();
    }
}
}
