<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Admin\AutoAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\CRMController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\Admin\ReserveContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ContactController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Storage;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/cache', function () {
    \Artisan::call('config:clear');
    \Artisan::call('optimize:clear');
    \Artisan::call('route:clear');
    \Artisan::call('view:clear');
    \Artisan::call('cache:clear');

    return '<h3>Caches have been cleared successfully!</h3>';
});
Route::get('/download-logs', function () {
    $logPath = storage_path('logs');
    $zipFile = storage_path('app/logs.zip');

    if (file_exists($zipFile)) {
        unlink($zipFile);
    }

    $zip = new \ZipArchive();
    if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
        $files = File::files($logPath);

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
    } else {
        abort(500, 'Failed to create ZIP archive.');
    }

    return response()->download($zipFile)->deleteFileAfterSend(true);
});


Route::get('/laravel-logs', function () {
    $logFile = storage_path('logs/laravel.log');

    if (!File::exists($logFile)) {
        abort(404, 'Log file not found.');
    }

    return response()->download($logFile, 'laravel.log');
});

Route::get('/clear-logs', function () {
  $cutoff = Carbon::now()->subMonth();

        // Delete from process_contacts
        $pcDeleted = DB::table('proccess_contacts')
            ->where('created_at', '<', $cutoff)
            ->delete();

        // Delete from savejob_logs
        $slDeleted = DB::table('savejob_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();
        // Delete from savejob_logs
        $slDeleted = DB::table('logs')
            ->where('created_at', '<', $cutoff)
            ->delete();
            $logPath = storage_path('logs');

                // Get all files from logs folder
                $files = File::files($logPath);

                foreach ($files as $file) {
                    File::delete($file);
                }

        return response()->json(['success' => 'Logs cleared successfully!']);
});
Route::get('/admin/reserve-contact/cleanup', function () {
    try {
        $thresholdDate = Carbon::now()->subMonth();

        $deletedCount = DB::table('reserve_contacts')->where('created_at', '<', $thresholdDate)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "$deletedCount old reserve contact(s) deleted successfully.",
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to delete old contacts: ' . $e->getMessage(),
        ], 500);
    }
});
Route::get('/download-sql', function () {
       set_time_limit(0);
    $filePath = '/var/www/html/xl_lead_distribution.sql'; // Replace with your actual filename
    $fileName = 'database-backup.sql'; // Desired download filename

    if (!File::exists($filePath)) {
        abort(404, 'SQL file not found.');
    }

    return Response::download($filePath, $fileName);
});
Route::get('/check/json/{email}', function ($email) {
    $contact=\App\Models\ProccessContact::where('email', $email)->first();
    if($contact){
        return  json_decode(base64_decode($contact->contact_json), true);;
    }else{
        $contact=\App\Models\Contact::where('email', $email)->first();
        if($contact){
            return  json_decode(base64_decode($contact->contact_json), true);;
        }else{
            return 'Contact not found';
        }

    }


});
Route::get('/add/carrier/test/agent', function () {
    $formattedCarrierTypes=[
            "HealthSpring__17085",
            "HealthSpring__17086",
            "Devoted Health__17095",
            "Devoted Health__17097",
            "Devoted Health__17099",
            "Devoted Health__17100",
            "Devoted Health__17102",
            "Devoted Health__17103",
            "Devoted Health__17104",
            "Devoted Health__17105",
            "Alignment Health Plan__17108",
            "Mass General Brigham Health Plan__17109",
            "American Health Advantage of Iowa__17110",
            "AmeriHealth Caritas VIP Care__17111",
            "AmeriHealth Caritas VIP Care__17112",
            "BlueCross BlueShield of Tennessee__17113",
            "Cox HealthPlans__17124",
            "Essence Healthcare__17125",
            "Essence Healthcare__17127",
            "Essence Healthcare__17128",
            "Gold Kidney of Arizona__17131",
            "Jefferson Health Plans__17132",
            "L.A. Care Health Plan__17133",
            "Molina Healthcare of Nevada__17135",
            "Patrius Health__17136",
            "Providence Medicare Advantage Plans__17137",
            "Align powered by Sanford Health Plan__17139",
            "Santa Clara Family Health Plan__17140",
            "Signature Advantage (HMO SNP)__17141",
            "SCAN Health Plan New Mexico__17144",
            "HealthSpring__17145",
            "American Health Advantage of Indiana__17146",
            "AmeriHealth__17147",
            "Champion Health Plan__17149",
            "Champion Heath Plan__17150",
            "eternalHealth__17152",
            "Gold Kidney Health Plan__17153",
            "Jefferson Health Plans Medicare__17154",
            "Johns Hopkins Advantage MD__17155",
            "Medical Associates Clinic Health Plan of Wisconsin__17156",
            "Peak Health__17158",
            "Provider Partners Health Plan of North Carolina__17159",
            "Select Health__17160",
            "Trinity Health Plan of Michigan__17162",
            "Verda Health Plan of Texas__17164",
            "Wellcare__17165",
            "Humana__17167",
            "Molina Healthcare of Arizona__17168",
            "Molina Healthcare of Arizona__17169",
            "Molina Healthcare of Illinois__17170",
            "Devoted Health__17173",
            "Devoted Health__17174",
            "Devoted Health__17175",
            "Devoted Health__17176",
            "Devoted Health__17177",
            "Devoted Health__17178",
            "Devoted Health__17179",
            "Healthy Blue__17180",
            "Wellcare__17181",
            "Wellcare__17182",
            "American Health Advantage of Pennsylvania__17198",
            "CareFirst BlueCross BlueShield Medicare Advantage__17199",
            "Excellus Health Plan Community Care LLC__17201",
            "Great Plains Medicare Advantage__17202",
            "Healthy Mississippi, Inc.__17203",
            "Highmark Blue Cross Blue Shield__17204",
            "iCircle Services of the Finger Lakes, Inc__17205",
            "Jefferson Health Plans__17206",
            "Provider Partners Health Plan of Indiana__17207",
            "SECUR Health Plan__17208",
            "UCLA Health Medicare Advantage Plan__17213",
            "Zing Health__17214",
            "Zing Health__17215",
            "Zing Health__17216",
            "Anthem Pathways__17225",
            "Wellpoint__17226",
            "Wellpoint__17227",
            "Wellcare by Ohana Plan__17228",
            "Wellcare By Absolute Total Care__17229",
            "Wellcare By Meridian__17230",
            "Wellcare By Meridian__17231",
            "Molina Healthcare of Iowa__17233",
            "Devoted Health__17234",
            "Devoted Health__17235",
            "Devoted Health__17236",
            "Devoted Health__17237",
            "Devoted Health__17238",
            "Devoted Health__17239",
            "Devoted Health__17240",
            "Devoted Health__17241",
            "Devoted Health__17242",
            "Humana__17243",
            "Humana__17244",
            "Humana__17245",
            "Humana__17246",
            "Humana__17247",
            "Humana__17248",
            "Humana__17249",
            "Humana__17250",
            "Humana__17251",
            "Humana__17252",
            "Humana__17253",
            "Humana__17254",
            "Humana__17255",
            "Humana__17256",
            "Humana__17257",
            "AgeRight Advantage__17258",
            "Alameda Alliance for Health__17259",
            "AmeriHealth Caritas VIP Care__17260",
            "AmeriHealth Caritas VIP Care__17261",
            "AmeriHealth Caritas VIP Care (HMO-SNP)__17262",
            "CenCal CareConnect__17263",
            "Central California Alliance for Health__17264",
            "Community Health Plan of Imperial Valley__17266",
            "Contra Costa Health Care Plus (HMO D-SNP)__17267",
            "Elite Health Plan, Inc.__17268",
            "Gold Coast Health Plan__17269",
            "HAP CareSource MI Coordinated Health__17270",
            "Health Plan of San Joaquin / Mountain Valley Healt__17271",
            "Highmark Blue Cross Blue Shield__17272",
            "HMSA Akamai Advantage Dual Care__17273",
            "Kern Family Health Care Medicare (D-SNP)__17274",
            "Lagniappe Advantage__17275",
            "MyAdvocate Medicare Advantage__17276",
            "NHC Advantage__17277",
            "Peak Health__17278",
            "Perennial Advantage__17279",
            "Provider Partners Health Plans__17280",
            "San Francisco Health Plan__17281",
            "SCAN Health Plan__17282",
            "Upper Peninsula Health Plan (UPHP) MI Coordinated __17283",
            "Verda Health Plan of Arizona__17284",
            "WyoBlue Advantage__17285",
            "Communicare Advantage__17286",
            "Healthy Blue__71819",
    ];
   $agentId = 247;
$leadType = 3;

$insertData = [];

foreach ($formattedCarrierTypes as $carrier) {
    $insertData[] = [
        'agent_id'     => $agentId,
        'lead_type'    => $leadType,
        'carrier_type' => $carrier,

    ];
}

// Bulk insert
\DB::table('agent_carrier_types')->insert($insertData);

});
Route::middleware('auth' )->group(function () {
    Route::get('/filemanager', function () {
        return require public_path('filemanager/index.php');
    });
});

Route::get('/', function () {
    return view('auth.login');
})->name('login');
Auth::routes();

Route::prefix('authorization')->name('crm.')->group(function () {
    Route::get('/crm/oauth/callback', [CRMController::class, 'crmCallback'])->name('oauth_callback');
});
Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats'])->name('dashboard.stats');
         Route::get('/detail/dashboard/stats', [DashboardController::class, 'detailDashboard'])->name('dashboard.details');
        Route::get('user', [UserController::class, 'index'])->name('user.index');
        Route::post('user/{id?}', [UserController::class, 'store'])->name('user.store');
        Route::get('user/edit/{id}', [UserController::class, 'edit'])->name('user.edit');
        Route::get('user/status/{id?}', [UserController::class, 'status'])->name('user.status');
        Route::get('/profile', [UserController::class, 'profile'])->name('profile');
        Route::post('/profile-save', [UserController::class, 'general'])->name('profile.save');
        Route::post('/password-save', [UserController::class, 'changePassword'])->name('password.save');
        Route::post('/email-change', [UserController::class, 'changeEmail'])->name('email.save');
        Route::delete('user/delete/{id}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::post('/fetch/alluserlocation' , [UserController::class,'userFetchAllLocation']);
        Route::get('/agent/user/', [AgentController::class, 'agentUser'])->name('agent.user');
        Route::POST('/agent/user/', [AgentController::class, 'agentUserSave'])->name('agent.user.save');
        Route::get('/log' , [ReserveContactController::class,'log'])->name('log.index');
        //Agent route
        Route::get('agent/status/{id?}', [AgentController::class, 'agent'])->name('agent.status');
        Route::get('/campaign/agent/', [AgentController::class, 'agentCampaignSearch'])->name('agent.campaign');
                Route::get('/get-carrier-types', function (Request $request) {
            $leadtype=$request->get('lead_type', 'ACA');
            $allTypes = getCarrierType($leadtype);
            $search   = strtolower($request->get('q', ''));

            // Filter by query if provided
            $filtered = array_filter($allTypes, function ($type) use ($search) {
                return $search === '' || str_contains(strtolower($type), $search);
            });

            // Return in Select2 format
            $results = array_map(function ($type) {
                return [
                    'id'   => $type,
                    'text' => $type,
                ];
            }, $filtered);

            return response()->json([
                'results'    => array_values($results),
                'pagination' => ['more' => false],
            ]);
        })->name('getCarrierTypes');
        // Resource routes for states, agents, and campaigns
        Route::resource('states', StateController::class);
        Route::get('save-or-update', [StateController::class,'saveOrUpdateState']);
        Route::resource('agents', AgentController::class);
        Route::resource('campaigns', CampaignController::class);
        Route::get('/agent/weightage/{agentId}/{campaignId?}', [ CampaignController::class, 'getAgentWeightage']);
        Route::get('/reserve/contact' , [ReserveContactController::class,'index'])->name('reserve.contact');
        Route::get('/sent/contacts' , [ContactController::class,'index'])->name('sent.contact');
        Route::get('/campaign/show' , [CampaignController::class,'campaignShow'])->name('campaign.show');
        Route::get('state/reserve/{state?}/{leadtype?}', [ReserveContactController::class, 'fetchState']);

        Route::post('/assign-agent', [ReserveContactController::class, 'assignAgent']);
        //fro Searching
        Route::get('/search/agent/', [AgentController::class, 'searchAgentByAjax'])->name('agent.search');
        Route::get('/search/locations/', [DashboardController::class, 'searchLocationByAjax'])->name('location.search');
        Route::get('/search/state/', [StateController::class, 'searchStateByAjax'])->name('state.search');
        Route::get('/search/campaign/', [CampaignController::class, 'searchCampaignByAjax'])->name('campaign.search');
        //for CustomField
        Route::get('get/custom/field', [DashboardController::class, 'getCustomField'])->name('customfield.index');
        Route::post('/sync-custom-fields', [UserController::class, 'syncCustomFieldsByLocation'])->name('sync.custom.fields');
        Route::post('/update-custom-field', [UserController::class, 'updateCustomField'])->name('update.custom.field');
        Route::get('get/job/logs', [DashboardController::class, 'getJobLogs'])->name('job.logs');

          //Change agent_id
          Route::post('change/agent/id',[DashboardController::class ,'changeAgent'])->name('change.agent.id');
    });
    Route::prefix('settings')->name('setting.')->group(function () {
        Route::get('/index', [SettingController::class, 'index'])->name('index');
        Route::post('/save', [SettingController::class, 'save'])->name('save');
        Route::post('settings/logo', [SettingController::class, 'saveLogo'])->name('saveLogo');
    });


});
Route::get('check/auth', [AutoAuthController::class, 'connect'])->name('auth.check');
Route::get('check/auth/error', [AutoAuthController::class, 'authError'])->name('error');
Route::get('checking/auth', [AutoAuthController::class, 'authChecking'])->name('admin.auth.checking');

Route::get('file-manager', function () {
    return view('file-manager.index');
});
Route::get('/loginwith/{id}', function ($id) {
    $user = \App\Models\User::findOrFail($id);

    if ($user) {
        $currentUser = Auth::user();

        if (in_array($currentUser->role, [0, 1])) {
            if ($user->role == 1) {
                session()->put('super_admin', $currentUser);
            } else {
                session()->put('company_admin', $currentUser);
            }

            Auth::loginUsingId($user->id);
        }
    }
    return redirect()->intended('admin/dashboard');
})->name('admin.user.autoLogin');

Route::get('/backtoadmin', function () {
    if (request()->has('admin') && session()->has('super_admin')) {
        Auth::login(session('super_admin'));
        session()->forget(['super_admin', 'company_admin']);
    } elseif (request()->has('company') && session()->has('company_admin')) {
        Auth::login(session('company_admin'));
        session()->forget('company_admin');
    } else {
        return redirect()->route('login')->withErrors('No admin session available.');
    }
    return redirect()->intended('admin/dashboard');
})->name('backtoadmin');


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
