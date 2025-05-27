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
        Route::get('/compaign/agent/', [AgentController::class, 'agentCompaignSearch'])->name('agent.compaign');
        // Resource routes for states, agents, and campaigns
        Route::resource('states', StateController::class);
        Route::get('save-or-update', [StateController::class,'saveOrUpdateState']);
        Route::resource('agents', AgentController::class);
        Route::resource('campaigns', CampaignController::class);
        Route::get('/agent/weightage/{agentId}/{campaignId?}', [ CampaignController::class, 'getAgentWeightage']);
        Route::get('/reserve/contact' , [ReserveContactController::class,'index'])->name('reserve.contact');
        Route::get('/sent/contacts' , [ContactController::class,'index'])->name('sent.contact');
        Route::get('/campaign/show' , [CampaignController::class,'campaignShow'])->name('campaign.show');
        Route::get('state/reserve/{state?}',[ReserveContactController::class,'fetchState']);
        Route::post('/assign-agent', [ReserveContactController::class, 'assignAgent']); 
        //fro Searching
        Route::get('/search/agent/', [AgentController::class, 'searchAgentByAjax'])->name('agent.search');
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
