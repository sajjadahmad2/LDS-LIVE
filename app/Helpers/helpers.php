<?php
use App\Models\CustomField;
use App\Models\LeadType;
use App\Models\Setting;
use App\Models\State;
use App\Models\TrackLog;
use App\Models\User;
use Illuminate\Support\Str;
function supersetting($key, $default = '')
{
    $setting = Setting::where(['user_id' => 1, 'key' => $key])->first();
    $value   = $setting->value ?? $default;
    return $value;
}
function loginUser()
{
    return auth()->user() ?? null;
}
if (! function_exists('formatLog')) {
    function formatLog($message)
    {
        $pattern   = '/(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/';
        $formatted = preg_replace($pattern, '<br>', trim($message));
        return $formatted;
    }
}
if (! function_exists('appendJobLog')) {
    function appendJobLog($contactId, $newMessage)
    {
        try {
            $log = \App\Models\SaveJobLog::firstOrCreate(['contact_id' => $contactId]);

            $timestamp        = now()->format('Y-m-d H:i:s');
            $formattedMessage = "[$timestamp] " . $newMessage;

            // Append the message on a new line
            $existingMessage = $log->message ?? '';
            $log->message    = $existingMessage === ''
                ? $formattedMessage
                : $existingMessage . "\n" . $formattedMessage;

            $log->save();
        } catch (\Throwable $e) {
            \Log::error('Failed to append job log', [
                'contact_id' => $contactId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}

if (! function_exists('getActions')) {
    /**
     * Generate action buttons for DataTables
     *
     * @param array $actions
     * @param string $route
     * @param int $id
     * @return string
     */
    function getActions(array $actions, string $route, int $id)
    {
        $html = '';

        if (isset($actions['edit']) && $actions['edit']) {
            $editUrl = route($route . '.edit', ['user' => $id]);
            $html .= '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a> ';
        }

        if (isset($actions['delete']) && $actions['delete']) {
            $deleteUrl = route($route . '.destroy', ['user' => $id]);
            $html .= '<a href="' . $deleteUrl . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
        }

        return $html;
    }
}
function findLeadTypeId($spaceType)
{
    if (is_null($spaceType)) {
        return 1;
    }
    $leadType = LeadType::where('name', "LIKE", "%$spaceType%")->first();
    if ($leadType) {
        return $leadType->id;
    }
    return 1;

}
//This Function give the user id of the Current logged in User
function isSuperAdmin()
{

    return (session('superadmin') && ! empty(session('superadmin'))) || is_role() == 'admin' || is_role() == 'company';
}

function customFields($customData, $agent)
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
        return [trim($key) => trim($value)];
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

function login_id($id = "")
{
    if (! empty($id)) {
        return $id;
    }

    if (auth()->user()) {
        $id = auth()->user()->id;
    } elseif (session('uid')) {
        $id = session('uid');
    } elseif (Cache::has('user_ids321')) {
        $id = Cache::get('user_ids321');
    }

    return $id;
}

// function superAdmin()
// {
//    return 1;
// }
//This Function give the role of the Current logged in User
function is_role($user = null)
{
    if (! $user) {
        if (auth()->user()) {
            $user = auth()->user();
        }

    }
    if ($user) {
        if ($user->role == 0) {
            return 'superadmin';
        } elseif ($user->role == 1) {
            return 'admin';
        } elseif ($user->role == 2) {
            return 'company';
        } else {
            return 'user';
        }
    }
    return null;
}

function save_settings($key, $value = '', $userid = null)
{
    if (is_null($userid)) {
        $user_id = login_id();
    } else {
        $user_id = $userid;
    }
    // 'user_id' => $user_id;
    $setting = Setting::updateOrCreate(
        ['key' => $key],
        [
            'value'   => $value,
            'user_id' => $user_id,
            'key'     => $key,
        ]
    );
    return $setting;
}

function uploadFile($file, $path, $name)
{
    // Store the file in the 'public' disk and return the file path
    $name = $name . '.' . $file->getClientOriginalExtension();
    $file->storeAs('public/' . $path, $name); // Storing in the 'storage/app/public' directory

    // Return the relative path to the file
    return 'storage/' . $path . '/' . $name;
}

function getCarrierType($leadType = null)
{
    $carriers = [
        'ACA'                => [
            // UnitedHealthcare
            "UnitedHealthcare",
            "UnitedHealthcare Insurance Company of New York",
            "UnitedHealthcare Insurance Company of the River Valley",
            "UnitedHealthcare Insurance Company",
            "UnitedHealthcare Life Insurance Company",
            "UnitedHealthcare of the Mid-Atlantic, Inc.",
            "UnitedHealthcare Ins Co of Ohio",
            "UnitedHealthcare Oxford",
            "UnitedHealthcare of Ohio, Inc",
            "UnitedHealthcare of Illinois Inc",
            "UnitedHealthcare Insurance Company of Ohio",
            "UnitedHealthcare of the Mid-Atlantic",
            "UnitedHealthcare of Alabama, Inc",
            "UnitedHealthcare Ins Co of Illinois",
            "UnitedHealthcare of the Midlands, Inc.",
            "UnitedHealthcare of North Carolina, Inc.",
            "UnitedHealthcare of New England Inc.",
            "UnitedHealthcare of Oregon, Inc.",
            "UnitedHealthcare of New Mexico, Inc",
            "UnitedHealthcare Texas CHIP",
            "UnitedHealthcare of Kentucky, Ltd.",
            "UnitedHealthcare Benefits of Texas, Inc.",
            "UnitedHealthcare Insurance Company of Illinois",
            "UnitedHealthcare of Texas",
            // Oscar
            "Oscar Health Plan of California",
            "Oscar Health Plan, Inc.",
            "Oscar Insurance Company",
            "Oscar Insurance Company of Illinois, Inc",
            "Oscar Garden State Insurance Corporation",
            "Oscar Health Insurance",
            "Oscar Health Plan of South Carolina, Inc.",
            // Blue Cross Blue Shield
            "Blue Cross Blue Shield of Wyoming",
            "Blue Cross and Blue Shield of NC",
            "Blue Cross & Blue Shield of Mississippi",
            "Anthem Blue Cross Life and Health Insurance Company, Inc.",
            "Blue Cross Blue Shield of Vermont",
            "Blue Cross & Blue Shield of Rhode Island",
            "Blue Cross Blue Shield of Michigan Mutual Insurance Company",
            "Anthem Blue Cross and Blue Shield",
            "Blue Cross and Blue Shield of Kansas, Inc.",
            "Premera Blue Cross HMO",
            "Blue Cross Blue Shield of Massachusetts",
            "Anthem Blue Cross",
            "Blue Cross and Blue Shield of Nebraska",
            "Blue Cross Blue Shield of Montana",
            "Blue Cross and Blue Shield of Montana",
            "Highmark Blue Cross Blue Shield West Virginia",
            "Independence Blue Cross",
            "Blue Cross Blue Shield of Minnesota",
            "Blue Cross and Blue Shield of Texas",
            "Wellmark Blue Cross and Blue Shield of Iowa",
            "Blue Cross and Blue Shield of Kansas City",
            "Blue Cross and Blue Shield of Illinois",
            "Blue Cross of Northeastern Pennsylvania",
            "Blue Cross Blue Shield of North Dakota",
            "Premera Blue Cross Blue Shield of Alaska",
            "Highmark Blue Cross Blue Shield",
            // Aetna / Cigna / Anthem / Molina
            "Aetna",
            "Aetna Life Insurance Company",
            "Aetna CVS Health",
            "Aetna HealthAssurance Pennsylvania, Inc.",
            "BannerAetna",
            "Allina Health and Aetna Insurance Company",
            "Aetna Health Inc. (a GA corp.)",
            "Aetna Health Insurance Company",
            "Texas Health + Aetna Health Insurance Company",
            "Banner Health and Aetna Health Insurance Company",
            "Aetna Health and Life Insurance Company",
            "Aetna Health Inc.",
            "CIGNA",
            "Cigna Healthcare of South Carolina",
            "Cigna Healthcare",
            "Cigna HealthCare of Georgia, Inc",
            "Cigna Worldwide Insurance Company",
            "Cigna HealthCare of St. Louis, Inc",
            "Anthem Insurance Companies, Inc.",
            "Wisconsin Collaborative Insurance Company (Anthem BCBS)",
            "Molina Healthcare",
            // Ambetter
            "Ambetter Health",
            "Ambetter from WellCare of New Jersey",
            "Ambetter from Nebraska Total Care",
            "Ambetter of Illinois",
            "Ambetter from Home State Health",
            "Ambetter from Superior HealthPlan",
            "Ambetter from CeltiCare Health Plan of MA",
            "Ambetter from MHS Health WI",
            "Ambetter from Sunflower Health Plan",
            "Ambetter from Arkansas Health & Wellness",
            "Ambetter from Western Sky Community Care",
            "Ambetter from SilverSummit Healthplan",
            "Ambetter of Alabama",
            "Ambetter from Meridian",
            "Ambetter from Louisiana Healthcare Connections",
            "Ambetter from Coordinated Care",
            "Ambetter of Oklahoma",
            "Ambetter Health of Delaware",
            "Ambetter of Tennessee",
            "Ambetter from Peach State Health Plan",
            "Ambetter from WellCare of Kentucky",
            "Ambetter from NH Healthy Families",
            "Ambetter of North Carolina",
            "Ambetter from Absolute Total Care",
            "Ambetter from PA Health & Wellness",
            "Ambetter from Magnolia Health",
            "Ambetter from Arizona Complete Health",
            "Ambetter from Sunshine Health",
            "Ambetter from Buckeye Health Plan",
            // AmeriHealth / WellCare
            "AmeriHealth HMO, Inc.",
            "AmeriHealth Caritas Next",
            "QCC Ins Company d/b/a AmeriHealth Ins Co",
            "Amerihealth Caritas Iowa",
            "AmeriHealth New Jersey",
            "Wellpoint",
            "WellCare of Kentucky",
            "WellCare Health Insurance of Kentucky, Inc.",
            "WellCare of New York",
            "WellCare of North Carolina",
        ],

        // You can add other lead types here later
        'Final Expense - FE' => [
            "American National Life Insurance Company of Texas",
            "Guarantee Trust Life Insurance Company",
            "Union Fidelity Life Insurance Company",
            "Transamerica Life Insurance Company",
            "Washington National Insurance Company",
            "Thrivent Financial for Lutherans",
            "Knights of Columbus",
            "United of Omaha Life Insurance Company",
            "National Guardian Life Insurance Company",
            "Pekin Life Insurance Company",
            "American General Life Insurance Company",
            "TransAmerica Premier Life Insurance",
            "American Income Life Insurance Co",
            "Liberty National Life Insurance Co",
            "United American Insurance Co",
            "Globe Life and Accident Insurance Co",
            "Mutual of Omaha Insurance Company",
            "American General Life and Accident Insurance Company",
            "Standard Life and Accident Insurance Company",
            "Fidelity Life Association",
            "Assurity Life Insurance Company",
            "National Guardian Life Insurance",
            "United of Omaha Life Insurance Company ",
            "Loyal American Life Insurance Company",
            "Pekin LIfe Insurance Company",
            "American General Life and Accident",
            "Physicians Mutual",
            "NATIONAL GUARDIAN LIFE INSURANCE COMPANY",
        ],
        'Medicare'           => [
            "UnitedHealthcare",
            "Humana",
            "Aetna",
            "Cigna",
            "Anthem/BCBS (various states)",
            "Kaiser Permanente (multi-state, but limited regions)",
            "Molina Healthcare",
            "Devoted Health",
            "WellCare (Centene)",
            "SCAN Health Plan",
            "Florida Blue",
            "HealthPartners (MN)",
            "UCare (MN)",
            "Highmark (PA)",
            "UPMC (PA)",
            "Independence BCBS (PA)",
            "Presbyterian Health Plan (NM)",
            "Quartz (WI)",
            "Security Health Plan (WI)",
            "Peoples Health (LA)",
            "Tufts Health Plan (MA)",
            "EmblemHealth (NY)",
            "Fidelis Care (NY)",
            "Healthfirst (NY)",
            "Banner/BCBS AZ",
            "Arkansas BCBS",
            "CareFirst BCBS (MD/DC)",
            "Sanford Health (ND/SD)",
            "HMSA (HI)",
            "SelectHealth (UT)",
            "Regence (OR/WA/ID/UT)",
            "Premera (WA)",
            "Sentara (VA)",
            "CarePlus Health Plans, Inc.",
            "Florida Blue HMO",
            "Ascension Complete",
            "Lasso Healthcare",
            "Clear Spring Health",
            "Mutual of Omaha Rx",
            "Express Scripts Medicare",
            "Elixir Insurance",
            "Molina Healthcare of Florida",
            "Alignment Health Plan",
            "Cigna Healthcare",
            "Longevity Health Plan",
            "Wellcare by Allwell",
            "Simply Healthcare Plans, Inc.",
            "Simply Healthcare",
            "Gold Kidney Health Plan",
            "AHF",
            "Allwell",
            "American Health Advantage of Florida",
            "Florida Complete Care",
            "Globe",
            "United America",
            "Essence",
            "Kelsey Advantage",
            "bcbs",
        ],

    ];

    // Return only requested type if given
    if ($leadType !== null && isset($carriers[$leadType])) {

        return $carriers[$leadType];
    }

    return $carriers['ACA'];
}
function saveStatusLog($sourceLocation, $campaignId, $agentId, $status = null, $reason = null)
{
    // Validate required fields
    if (empty($sourceLocation) || empty($campaignId) || empty($agentId)) {
        throw new InvalidArgumentException('Source location, campaign ID, and agent ID are required.');
    }

    // Create a new TrackLog entry
    return TrackLog::create([
        'source_location' => $sourceLocation,
        'campaign_id'     => $campaignId,
        'sent_to'         => $agentId,
        'status'          => $status,
        'reason'          => $reason,
    ]);
}
function CreateContactData($data, $agent = null, $campaign, $json = true, $dbcon = false, $leadTypeId)
{
    // Ensure $data is always an array
    if ($data instanceof \Illuminate\Support\Collection  || is_object($data)) {
        $data = $data->toArray();
    }
    $contactData = [
        'first_name'                                  => $data['first_name'] ?? null,
        'last_name'                                   => $data['last_name'] ?? null,
        'email'                                       => $data['email'] ?? null,
        'phone'                                       => $data['phone'] ?? null,
        'address1'                                    => $data['address1'] ?? null,
        'tags'                                        => isset($data['tags']) ? json_encode($data['tags']) : null, // Encode as JSON if it's an array
        'full_address'                                => $data['full_address'] ?? null,
        'country'                                     => $data['country'] ?? null,
        'source'                                      => $data['contact_source'] ?? null,
        'date_added'                                  => isset($data['date_created']) ? \Carbon\Carbon::parse($data['date_created']) : null,
        'city'                                        => $data['city'] ?? null,
        'state'                                       => $data['state'] ?? null,
        'postal_code'                                 => $data['postal_code'] ?? null,
        'location_id'                                 => $dbcon ? $data['location_id'] : $data['location']['id'] ?? null,
        'contact_id'                                  => $data['contact_id'] ?? null,
        'location'                                    => $dbcon ? $data['location'] : json_encode($data['location']) ?? null,
        'address'                                     => $dbcon ? $data['address'] : $data['location']['fullAddress'] ?? null,
        'user_id'                                     => $agent ? $agent->user_id : null,
        'agent_id'                                    => $agent ? $agent->id : null,
        'campaign_id'                                 => $campaign->id ?? null,
        // Custom data
        'address'                                     => $data['address'] ?? null,
        'city'                                        => $data['city'] ?? null,
        'state'                                       => $data['state'] ?? null,
        'postal_code'                                 => $data['postal_code'] ?? null,
        'trusted_form_ping_url'                       => $data['trusted_form_ping_url'] ?? null,
        'ip_address'                                  => $data['ip_address'] ?? null,
        'trusted_form_cert_url'                       => $data['trusted_form_cert_url'] ?? null,
        'your_gender'                                 => $data['your_gender?'] ?? null,
        'social_security'                             => $data['social_security'] ?? null,
        'marital_status'                              => $data['marital_status'] ?? null,
        'spouses_first_name'                          => $data['spouses_first_name'] ?? null,
        'spouses_last_name'                           => $data['spouses_last_name'] ?? null,
        //'spouse_gender' => $data['spouse_gende'] ?? null,
        'spouse_date_of_birth'                        => $data['spouse_date_of_birth'] ?? null,
        'do_you_want_to_enroll_spouse_as_well'        => $data['do_you_want_to_enroll_spouse_as_well'] ?? null,
        'spouse_ssn'                                  => $data['spouse_ssn'] ?? null,
        'tax_dependents_typically_children'           => $data['tax_dependents_typically_children'] ?? null,
        'number_of_tax_dependants_typically_children' => $data['number_of_tax_dependants_typically_children'] ?? null,
        'wish_to_enroll_your_dependents'              => $data['wish_to_enroll_your_dependents'] ?? null,
        'tax_dependants_date_of_births'               => $data['tax_dependants_date_of_births'] ?? null,
        'disqualify_lead'                             => $data['disqualify_lead'] ?? null,
        'company_name_if_self_employed'               => $data['company_name_if_self_employed'] ?? null,
        'projected_annual_income'                     => $data['projected_annual_income'] ?? null,
        'employment_status'                           => $data['employment_status'] ?? null,
        'signature'                                   => $data['signature'] ?? null,
        'my_signature'                                => $my_signature ?? null,
        'application_informatio_my_signature'         => $data['application_informatio_my_signature'] ?? null,
        'plan_name'                                   => $data['plan_name'] ?? null,
        'plan_carrier_name'                           => $data['plan_carrier_name'] ?? null,
        'plan_id'                                     => $data['plan_id'] ?? null,
        'plan_type'                                   => $data['plan_type'] ?? null,
        'brochure_url'                                => $data['brochure_url'] ?? null,
        'benefits_url'                                => $data['benefits_url'] ?? null,
        'date_of_birth'                               => isset($data['date_of_birth']) ? \Carbon\Carbon::parse($data['date_of_birth']) : null,
        'selected_plan_image'                         => $data['selected_plan_image'] ?? null,
        'contact_json'                                => $json ? $data['contact_json'] : base64_encode(json_encode($data)),
        'lead_type'                                   => $leadTypeId ?? 1,
    ];
    return $contactData;
}
function state()
{
    return [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ];
}

function saveOrUpdateStateInDatabase($stateData)
{
    //dd($stateData);
    foreach ($stateData as $shortForm => $stateName) {
        //dd($shortForm);
        // Check if the state exists in the database
        $existingState = State::where('state', $stateName)->first();

        if ($existingState) {
            //dd($shortForm ,  $stateName);
            // Update the state if it exists
            $existingState->state      = $stateName;
            $existingState->short_form = $shortForm;
            $existingState->save();
            //dd($existingState);
        } else {
            //dd($shortForm ,  $stateName);
            // Create a new state if it does not exist
            State::create([
                'state'      => $stateName,
                'short_form' => $shortForm,
            ]);
            //dd($save);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'States have been saved or updated successfully.',
    ]);
}
// try {
//     if (! empty($customFieldData)) {
//         if (! empty($customData['customData'])) {
//             foreach ($customData['customData'] as $index => $dataValue) {
//                 $snakeCaseIndex = trim(Str::snake($index));

//                 foreach ($customFieldData as $field) {
//                     if ($field->key === $snakeCaseIndex && (is_null($field->field_value) || $field->field_value === '')) {
//                         if (! empty($dataValue)) {
//                             $field->field_value = $dataValue;
//                         }
//                     }
//                 }
//             }
//         }
//     }
// } catch (\Exception $e) {
//     Log::error('Error while processing customData: ' . $e->getMessage());
// }
