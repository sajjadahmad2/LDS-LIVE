<?php
namespace App\Helpers;

use App\Jobs\ConnectionJob;
use App\Models\GhlAuth;
class CRM
{

    protected static $base_url = 'https://services.leadconnectorhq.com/';
    protected static $version  = '2021-07-28';
    protected static $crm      = GhlAuth::class;
    public static $lang_com    = 'Company';
    public static $lang_loc    = 'Location';

    protected static $userType = ['Company' => 'company_id', 'Location' => 'location_id'];
    //oauth.write oauth.readonly locations/customFields.write  locations/customFields.readonly
    public static $scopes       = 'calendars/events.readonly calendars/events.write calendars.write calendars.readonly companies.readonly businesses.write businesses.readonly calendars/groups.readonly calendars/groups.write calendars/resources.readonly calendars/resources.write campaigns.readonly conversations.readonly conversations.write conversations/message.readonly conversations/message.write conversations/reports.readonly contacts.readonly contacts.write objects/schema.readonly objects/schema.write objects/record.readonly objects/record.write courses.readonly courses.write forms.readonly forms.write invoices.readonly invoices.write invoices/schedule.readonly invoices/schedule.write invoices/template.readonly invoices/template.write links.readonly lc-email.readonly links.write locations.readonly locations.write locations/customValues.readonly locations/customValues.write locations/customFields.readonly locations/customFields.write locations/tasks.readonly locations/tasks.write locations/tags.readonly locations/tags.write locations/templates.readonly medias.readonly medias.write funnels/redirect.readonly funnels/funnel.readonly funnels/page.readonly funnels/pagecount.readonly oauth.write funnels/redirect.write opportunities.readonly oauth.readonly opportunities.write payments/orders.readonly payments/orders.write payments/integration.readonly payments/integration.write payments/transactions.readonly payments/subscriptions.readonly payments/custom-provider.readonly payments/custom-provider.write products.readonly products.write products/prices.readonly products/prices.write products/collection.readonly products/collection.write saas/company.read saas/company.write saas/location.read saas/location.write snapshots.readonly snapshots.write socialplanner/oauth.readonly socialplanner/oauth.write socialplanner/post.readonly socialplanner/post.write socialplanner/account.readonly socialplanner/account.write socialplanner/csv.readonly socialplanner/csv.write socialplanner/tag.readonly socialplanner/category.readonly store/shipping.readonly store/shipping.write store/setting.readonly store/setting.write surveys.readonly users.readonly users.write emails/builder.readonly emails/builder.write wordpress.site.readonly workflows.readonly blogs/post.write blogs/post-update.write blogs/check-slug.readonly blogs/category.readonly blogs/author.readonly socialplanner/category.write socialplanner/tag.write';
    protected static $no_token  = 'No Token';
    protected static $no_record = 'No Data';

    public static function getDefault($key, $def = '')
    {
        $def = supersetting($key, $def);
        //uncomment above function and change with desired function
        return $def;
    }

    public static function getCrmToken($where = [])
    {

        return static::$crm::where($where)->first();
    }

    public static function saveCrmToken($code, $company_id, $loc = null)
    {
        //dd($code, $company_id, $loc = null);
        $where = ['user_id' => $company_id];
        $type  = $code->userType;

        if ($type == self::$lang_loc) {
            $where['location_id'] = $code->locationId ?? '';
        }
        if ($type == self::$lang_com) {
            $where['company_id'] = $code->companyId ?? '';

        }
        $cmpid = $code->companyId ?? "";
        if (! empty($cmpid)) {
            $where['company_id'] = $cmpid;
        }
        $already = true;
        if (is_null($loc)) {
            $already = false;
            $loc     = self::getCrmToken($where);
            if (is_null($loc)) {
                $loc              = new static::$crm();
                $loc->location_id = $code->locationId ?? '';
                $loc->user_type   = $type;
                $loc->company_id  = $cmpid;
                $loc->user_id     = $company_id;
                $loc->crm_user_id = $code->userId ?? '';
            }
        }
        $loc->expires_in    = $code->expires_in ?? 0;
        $loc->access_token  = $code->access_token;
        $loc->refresh_token = $code->refresh_token;
        $loc->save();
        if ($already) {
            $loc->refresh();
        }
        //save all the location in the company table
        // ConnectionJob::dispatch($company_id, $loc);
       set_time_limit(0);
        $getalllocations = self::agencyV2($company_id, 'locations/search?limit=1000');

        if ($getalllocations && property_exists($getalllocations, 'locations')) {
            $locations = $getalllocations->locations;
            if (! empty($locations)) {
                foreach ($locations as $location) {
                    \App\Models\CompanyLocation::updateOrCreate(
                        [
                            'location_id' => $location->id ?? null, // Search criteria
                        ],
                        [
                            'user_id'        => $company_id,
                            'location_email' => $location->email ?? null,
                            'location_name'  => $location->name ?? null,
                            'company_id'     => $location->companyId ?? null,
                        ]
                    );
                    $locationId = \CRM::connectLocation($company_id, $location->id, $loc, $company_id);
                    if (isset($locationId->location_id)) {
                        if ($locationId->statusCode == 400) {
                            \Log::error('Bad Request: Invalid locationId or accessToken', [
                                'location_id' => $user->location_id,
                                'user_id'     => $token->user_id,
                                'response'    => $locationId,
                            ]);
                            continue;
                        }
                        $ghl = GhlAuth::where('location_id', $locationId->location_id)->where('user_id', $user->id)->first();
                    }
                }
            }
        }

        return $loc;
    }

    public static function makeCall($url, $method = 'get', $data = null, $headers = [], $json = true)
    {
        $curl           = curl_init();
        $methodl        = strtolower($method);
        $is_key_headers = array_is_list($headers);
        if (! $is_key_headers) {
            $headers1 = [];
            foreach ($headers as $key => $t) {
                $headers1[] = $key . ': ' . $t;
            }
            $headers = $headers1;
        }
        $jsonheader = 'content-type: application/json';
        if (! empty($data)) {
            if ((is_array($data) || is_object($data))) {
                if ($json) {
                    $data = json_encode($data);
                } else {
                    $data = json_decode(json_encode($data), true);
                    $data = http_build_query($data);
                }
            }
            if ($json) {
                $headers[] = $jsonheader;
            }
            if ($methodl != 'get') {
                curl_setopt_array($curl, [CURLOPT_POSTFIELDS => $data]);
            } else {
                $url = static::urlFix($url) . $data;
            }
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        if ($err != '') {
            $response = $err;
        }

        return $response;
    }

    public static function directConnect()
    {
        //dd(self::baseConnect());
        // for location level connectivity where auto auth is not present then change gohighlevel with leadconnectorhq for only subaccounts
        return 'https://marketplace.gohighlevel.com/oauth/chooselocation?' . self::baseConnect();
    }

    public static function baseConnect()
    {
        //dd(static::getDefault('crm_client_id[value]'));
        $callbackurl = route('crm.oauth_callback');
        $client_id   = static::getDefault('crm_client_id');
        //dd($client_id);
        return "response_type=code&redirect_uri=" . urlencode($callbackurl) . "&client_id=" . $client_id . "&scope=" . urlencode(static::$scopes);
    }
    public static function ConnectOauth($main_id, $token, $is_company = false, $user_id = null)
    {
        $tokenx = false;

        if (! empty($token)) {
            $loc       = $main_id;
            $type      = $is_company ? self::$lang_com : self::$lang_loc;
            $auth_type = self::$userType[$type];
            $locurl    = static::$base_url . "oauth/authorize?" . ($auth_type) . "=" . $loc . "&userType=" . $type . '&' . self::baseConnect();

            $red = self::makeCall($locurl, 'POST', null, [
                'Authorization: Bearer ' . $token,
                //'Version: 2021-04-15'
            ]);

            $red = json_decode($red);

            if ($red && property_exists($red, 'redirectUrl')) {
                $url   = $red->redirectUrl;
                $parts = parse_url($url);
                parse_str($parts['query'], $query);
                $code                 = $query['code'] ?? '';
                list($tokenx, $token) = self::go_and_get_token($code, '', $user_id);
            }

        }
        return $tokenx;
    }

    public static function getLocationAccessToken($user_id, $location_id, $token = null, $connectuid = null, $retries = 0)
    {
        //dd($user_id, $location_id, $token);
        if (! $token) {
            $token = self::getCrmToken(['user_id' => $user_id, 'user_type' => self::$lang_com]);
        }
        $resp = null;
        if ($token) {
            $response = self::makeCall(static::$base_url . "oauth/locationToken", 'POST', "companyId=" . $token->company_id . "&locationId=" . $location_id, [
                "Accept: application/json",
                "Authorization: Bearer " . $token->access_token,
                "Content-Type: application/x-www-form-urlencoded",
                "Version: " . static::$version,
            ], false);
            $resp = json_decode($response);

            if ($resp && property_exists($resp, 'access_token')) {
                if ($connectuid) {
                    $user_id = $connectuid;
                }
                $resp = self::saveCrmToken($resp, $user_id);
            } else if (self::isExpired($resp) && $retries == 0) {
                list($is_refresh, $token) = self::getRefreshToken($user_id, $token, true);
                if ($is_refresh) {
                    $response = self::getLocationAccessToken($user_id, $location_id, $token, $connectuid, $retries + 1);

                }
            }
        }
        return $resp;
    }

    public static function go_and_get_token($code, $type = "", $company_id = null, $loc = null)
    {
        //dd($code, $type, $company_id, $loc);
        // if ($type == 'reconnect') {
        //     $oldtype = $type;
        //     $type = '';
        // } else if (!empty($type)) {
        //     $type = '1';
        //     $oldtype = $type;
        // }
        $status = false;

        $error = [$status, 'Unable to update'];
        if (is_string($code)) {
            $code = self::crm_token($code, $type);
            //$type = $oldtype ?? $type;
            $code = json_decode($code);
        }
        if ($code) {
            if (! $company_id) {
                return $error;
            }
            if (property_exists($code, 'access_token')) {
                return [true, self::saveCrmToken($code, $company_id, $loc)];
            }

            if (property_exists($code, 'error_description')) {
                if (strpos($code->error_description, 'refresh token is invalid') !== false) {
                    try {
                        if ($loc) {
                            // $loc->delete();
                        }
                    } catch (\Throwable $th) {
                        //throw $th;
                    }

                }
                $error = [$status, $code->error_description];
            }
        }
        return $error;
    }

    public static function urlFix($url)
    {
        return (strpos($url, '?') !== false) ? '&' : '?';
    }

    public static function getRefreshToken($company_id, $location, $is_company = false)
    {

        $loc_time   = 30;
        $type       = $is_company ? self::$lang_com : self::$lang_loc;
        $user       = ! $is_company ? ($location->location_id ?? $company_id) : $company_id;
        $loc_block  = cache()->lock($type . '_token_refresh' . $user, $loc_time);
        $is_refresh = false;
        $code       = $location->refresh_token;
        try {
            list($is_refresh, $location) = $loc_block->block($loc_time, function () use ($code, $company_id, $location) {
                try {
                    $location->refresh();
                    if ($code != $location->refresh_token) {
                        return [true, $location];
                    }
                    return self::go_and_get_token($code, '1', $company_id, $location);
                } catch (\Throwable $th) {
                    //throw $th;
                }
                return [false, null];
            });
        } catch (\Exception $e) {

        }
        return [$is_refresh, $location];
    }

    public static function agencyV2($company_id, $urlmain = '', $method = 'get', $data = '', $headers = [], $json = false, $token = null, $retries = 0)
    {
        if (! $company_id) {
            return self::$no_record;
        }
        $url = $urlmain;
        if ($token) {
            $company = $token;
        } else {
            $company = self::getCrmToken(['user_id' => $company_id, 'user_type' => self::$lang_com]);
        }
        $access_token = $company->access_token ?? null;
        if (! $access_token || empty($access_token)) {
            return self::$no_token;
        }
        $main_url           = static::$base_url;
        $headers['Version'] = static::$version;
        //$companyId = $location->company_id;
        //$methodl = strtolower($method);
        $headers['Authorization'] = 'Bearer ' . $access_token;
        $url1                     = $main_url . $url;
        //dd($url1, $method, $data, $headers, $json);
        $cd = self::makeCall($url1, $method, $data, $headers, $json);
        $bd = json_decode($cd);
        if (self::isExpired($bd) && $retries == 0) {
            list($is_refresh, $token) = self::getRefreshToken($company_id, $company, true);
            if ($is_refresh) {
                return self::agencyV2($company_id, $url, $method, $data, $headers, $json, $token, $retries + 1);
            }
        }
        return $bd;
    }
    public static function getAgencyToken($company_id)
    {
        return static::getCrmToken(['user_id' => $company_id, 'user_type' => self::$lang_com]);
    }
    public static function getLocationToken($company_id, $location = '')
    {
        $data = ['user_id' => $company_id, 'user_type' => self::$lang_loc];
        if ($location != '') {
            $data['location_id'] = $location;
        }
        return static::getCrmToken($data);
    }
    public static function connectLocation($company_id, $location, $companyToken = null, $connectuid = null)
    {
        //dd($company_id, $location, $companyToken);
        $token = null;
        if (! $companyToken) {
            $companyToken = static::getAgencyToken($company_id);
        }

        if ($companyToken) {
            $token = static::getLocationAccessToken($company_id, $location, $companyToken, $connectuid);

        }
        return $token;
    }

    public static function crmV2Loc($company_id = null, $location_id, $urlmain = '', $method = 'get', $data = '', $token = '', $json = true)
    {
        if (! $company_id) {
            return self::$no_record;
        }
        $token = static::getLocationToken($company_id, $location_id);
        if (! $token) {
            $token = static::connectLocation($company_id, $location_id);
        }
        if (empty($token) || is_null($token)) {
            return self::$no_token;
        }
        return self::crmV2($company_id, $urlmain, $method, $data, [], $json, $location_id, $token);
    }

    public static function isExpired($bd)
    {

        return (isset($bd->error) && strtolower($bd->error) == 'unauthorized' && stripos($bd->error, 'authclass') === false) || (isset($bd->message) && ! is_object($bd->message) && strtolower($bd->message) == 'invalid jwt');
    }

    public static function crmV2($company_id, $urlmain = '', $method = 'get', $data = '', $headers = [], $json = false, $location_id = '', $location = null, $retries = 0)
    {

        $url = $urlmain;
        if (! $company_id) {
            return self::$no_record;
        }

        if (! $location) {

            $location = self::getLocationToken($company_id, $location_id);

            if (! $location) {
                return self::$no_record;
            }
        }


        $main_url = static::$base_url;
        //dd($main_url);
        $headers['Version'] = static::$version;
        $access_token       = $location->access_token ?? null;

        if (! $access_token) {
            return self::$no_token;
        }
        $location_id = $location->location_id ?? '';
        $company_id  = $location->company_id ?? '';
        $methodl     = strtolower($method);
        if ((strpos($url, 'templates') !== false || strpos($url, 'tags') !== false || strpos($url, 'custom') !== false || strpos($url, 'tasks/search') !== false) && strpos($url, 'locations/') === false) {
            if (strpos($url, 'custom-fields') !== false) {
                $url = str_replace('-fields', 'Fields', $url);

            }

            if (strpos($url, 'custom-values') !== false) {
                $url = str_replace('-values', 'Values', $url);
            }
            $url = 'locations/' . $location_id . '/' . $url;
        } else if ($methodl == 'get') {
            $urlap = self::urlFix($url);
            if (strpos($url, 'location_id=') === false && strpos($url, 'locationId=') === false && strpos($url, 'locations/') === false) {

                if (strpos($url, 'opportunities/search') !== false) {
                    $url .= $urlap . 'location_id=' . $location_id;
                } else {
                    $isinnot = true;
                    $uri     = ['users', 'opportunities', 'conversations', 'links', 'opportunities', 'notes', 'appointments', 'tasks', 'free-slots'];
                    foreach ($uri as $k) {
                        if (strpos($url, $k) != false) {
                            $isinnot = false;
                        }
                    }
                    if ($isinnot) {
                        $url .= $urlap . 'locationId=' . $location_id;
                    }
                }
            }
        }

        if (strpos($url, 'contacts') !== false) {
            if (strpos($url, 'q=') !== false) {
                $url = str_replace('q=', 'query=', $url);
            }
            if (strpos($url, 'lookup') !== false) {
                $url = str_replace('lookup', 'search/duplicate', $url);
                if (strpos($url, 'phone=') !== false) {
                    $url = str_replace('phone=', 'number=', $url);
                }
            }
        }
        $lastsl = '/';
        $sep    = '?';
        $slash  = explode($sep, $url);
        if (strpos($url, 'customFields') === false) {
            if (count($slash) > 1) {
                $urlpart   = $slash[0];
                $lastindex = substr($urlpart, -1);
                if ($lastindex != $lastsl) {
                    $urlpart .= $lastsl;
                }
                $url = $urlpart . $sep . $slash[1];

            } else {
                $lastindex = substr($url, -1);
                if ($lastindex != $lastsl) {
                    $url .= $lastsl;
                    $urlmain .= $lastsl;
                }
            }
        }
        $headers['Authorization'] = 'Bearer ' . $access_token;
        if ($json) {
            // $headers['Content-Type'] = "application/json";
        }
        $url1 = $main_url . $url;
        // dd($url1);
        $usertype = $location->user_type;
        $dat      = '';
        if (! empty($data)) {
            if (! is_string($data)) {
                $dat = json_encode($data);
            } else {
                $dat = $data;
            }
            try {
                $dat = json_decode($dat) ?? null;
            } catch (\Exception $e) {
                $dat = (object) $data;
            }
            if (property_exists($dat, 'company_id')) {
                unset($dat->company_id);
            }
            if (property_exists($dat, 'customField')) {
                $dat->customFields = $dat->customField;
                unset($dat->customField);
            }

            if ($methodl == 'post') {
                $uri      = ['businesses', 'calendars', 'contacts', 'conversations', 'links', 'opportunities', 'contacts/bulk/business'];
                $matching = str_replace('/', '', $urlmain);
                foreach ($uri as $k) {
                    if ($matching == $k) {
                        if (! property_exists($dat, 'locationId')) {
                            $dat->locationId = $location_id;
                        }
                    }
                }
            }
            if ($methodl == 'put' && strpos($url, 'contacts') !== false) {
                if (property_exists($dat, 'locationId')) {
                    unset($dat->locationId);
                }
                if (property_exists($dat, 'gender')) {
                    unset($dat->gender);
                }
            }

        }

        if (strpos($url1, 'status') !== false) {

        }
        //dd($url1, $method, $dat, $headers,$json);
        $cd = self::makeCall($url1, $method, $dat, $headers, $json);
        //dd($cd);
        $bd = json_decode($cd);
        if (self::isExpired($bd) && $retries == 0) {
            list($is_refresh, $location1) = self::getRefreshToken($company_id, $location, false);
            //dd($is_refresh, $location1,$location);
            if (! $is_refresh && $location) {
                $cmpid     = $location->user_id ?? $company_id;
                $getAgency = static::getAgencyToken($cmpid);
                if ($getAgency) {
                    $location1 = static::connectLocation($cmpid, $location->location_id, $getAgency, $company_id);
                    if ($location && $location1) {
                        $is_refresh = true;
                    }
                }
            }

            if ($is_refresh) {
                return self::crmV2($company_id, $url, $method, $data, $headers, $json, $location_id, $location1, $retries + 1);
            }

            // if (self::ConnectOauth($company)) {
            //     return self::crmV2($company_id, $urlmain, $method, $data, $headers, $json,$location_id,null,$retries+1);
            // }

        }
        return $bd;
    }

    public static function crm_token($code = '', $method = '')
    {
        $md = empty($method) ? 'code' : 'refresh_token';
        if (empty($code)) {
            return $md . ' is required';
        }
        $url                   = static::$base_url . 'oauth/token';
        $data                  = [];
        $data['client_id']     = static::getDefault('crm_client_id');
        $data['client_secret'] = static::getDefault('crm_client_secret');
        $data[$md]             = $code;
        $data['grant_type']    = empty($method) ? 'authorization_code' : 'refresh_token';
        $headers               = ['content-type: application/x-www-form-urlencoded'];
        return self::makeCall($url, 'POST', $data, $headers, false);
    }
}
