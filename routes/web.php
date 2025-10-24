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
            "Blue Cross Blue Shield of Arizona Advantage__10016",
            "Wellpoint__10018",
            "Health Choice Arizona__10027",
            "Wellcare__10029",
            "Humana__10033",
            "Mercy Care Advantage__10035",
            "UnitedHealthcare__10039",
            "Aetna Medicare__10100",
            "Anthem Blue Cross__10103",
            "Blue Shield of California__10108",
            "Anthem Blue Cross__10113",
            "Central Health Medicare Plan__10114",
            "Chinese Community Health Plan__10115",
            "Alignment Health Plan__10118",
            "Wellcare__10122",
            "Wellcare by Health Net__10130",
            "Health Plan of San Mateo__10131",
            "Kaiser Permanente__10138",
            "Molina Healthcare of California__10142",
            "SCAN Health Plan__10147",
            "UnitedHealthcare__10148",
            "SCAN Desert Health Plan__10149",
            "SCAN Health Plan__10150",
            "SCAN Health Plan__10187",
            "Aetna Medicare__10201",
            "UnitedHealthcare__10206",
            "Humana__10226",
            "KelseyCare Advantage__10227",
            "Molina Healthcare of Texas, Inc.__10229",
            "Baylor Scott & White Health Plan__10232",
            "Wellcare__10245",
            "Aetna Medicare__10301",
            "Essence Healthcare__10308",
            "Humana__10316",
            "Humana__10318",
            "Medical Associates Health Plan, Inc.__10321",
            "UnitedHealthcare__10324",
            "Wellcare__10334",
            "Anthem Blue Cross and Blue Shield__10402",
            "Humana__10417",
            "UnitedHealthcare__10420",
            "Aetna Medicare__10501",
            "Anthem Blue Cross and Blue Shield__10503",
            "UnitedHealthcare__10510",
            "Humana__10516",
            "Kaiser Permanente__10518",
            "Aetna Medicare__10600",
            "Anthem Blue Cross and Blue Shield__10605",
            "Senior Care Plus__10620",
            "BlueCross BlueShield of Tennessee__10705",
            "Humana__10707",
            "UnitedHealthcare__10721",
            "Anthem Blue Cross and Blue Shield__10802",
            "UnitedHealthcare__10805",
            "Essence Healthcare__10810",
            "Aetna Medicare__10813",
            "Humana__10818",
            "Aetna Medicare__10900",
            "Florida Blue__10904",
            "Capital Health Plan__10906",
            "CarePlus Health Plans__10907",
            "Florida Blue HMO__10915",
            "Freedom Health__10917",
            "Health First Health Plans, Inc.__10918",
            "HealthSun__10923",
            "Humana__10926",
            "Humana__10928",
            "UnitedHealthcare__10931",
            "Optimum__10933",
            "Wellcare__10956",
            "Simply__10984",
            "Aetna Medicare__11001",
            "Anthem Blue Cross and Blue Shield__11004",
            "UnitedHealthcare__11008",
            "Humana__11019",
            "Kaiser Permanente__11022",
            "Wellcare__11035",
            "Wellcare__11100",
            "Aetna Medicare__11102",
            "Anthem Blue Cross and Blue Shield__11104",
            "The Health Plan__11116",
            "Humana__11120",
            "MediGold__11124",
            "Molina Healthcare of Ohio__11125",
            "Paramount Elite Medicare Plans__11126",
            "PrimeTime Health Plan__11127",
            "UnitedHealthcare__11129",
            "Aetna Medicare__11201",
            "Humana__11212",
            "Regence BlueCross BlueShield of Utah__11215",
            "UnitedHealthcare__11218",
            "ATRIO Health Plans__11302",
            "CareOregon Advantage__11303",
            "AllCare Advantage__11304",
            "PacificSource Medicare__11307",
            "Kaiser Permanente__11317",
            "Providence Medicare Advantage Plans__11321",
            "Regence BlueCross BlueShield of Oregon__11322",
            "Samaritan Advantage Health Plan__11324",
            "UnitedHealthcare__11325",
            "Wellcare__11329",
            "Humana__11353",
            "Humana__11514",
            "VIVA Medicare__11527",
            "Blue Cross and Blue Shield of Alabama__11532",
            "Aetna Medicare__11601",
            "Elevate Medicare Advantage__11610",
            "UnitedHealthcare__11612",
            "Humana__11620",
            "Kaiser Permanente__11621",
            "Aetna Medicare__11701",
            "Kaiser Permanente__11713",
            "Aetna Medicare__11800",
            "Humana__11910",
            "Medical Associates Health Plan, Inc.__11912",
            "UnitedHealthcare__11914",
            "Aetna Medicare__11932",
            "Quartz Medicare Advantage__11952",
            "Blue Cross of Idaho__12001",
            "PacificSource Medicare__12052",
            "UnitedHealthcare__12107",
            "Humana__12113",
            "Aetna Medicare__12134",
            "Humana__12213",
            "Peoples Health__12217",
            "UnitedHealthcare__12219",
            "HAP Senior Plus (PPO)__12301",
            "Blue Care Network__12303",
            "Blue Cross Blue Shield of Michigan__12304",
            "UnitedHealthcare__12310",
            "HAP Senior Plus__12317",
            "Humana__12320",
            "Molina Healthcare of Michigan__12323",
            "Paramount Elite Medicare Plans__12324",
            "Priority Health Medicare__12326",
            "Humana__12411",
            "Humana__12510",
            "UnitedHealthcare__12515",
            "Humana__12615",
            "UnitedHealthcare__12619",
            "Blue Cross and Blue Shield of North Carolina__12632",
            "Humana__12712",
            "UnitedHealthcare__12715",
            "Aetna Medicare__12731",
            "UnitedHealthcare__12816",
            "CommunityCare Senior Health Plan (HMO)__12905",
            "Humana__12913",
            "UnitedHealthcare__12917",
            "GlobalHealth__12925",
            "Aetna Medicare__13000",
            "UnitedHealthcare__13012",
            "Highmark Wholecare Medicare Assured__13017",
            "Geisinger Gold__13019",
            "Highmark Inc.__13023",
            "Humana__13025",
            "Independence Blue Cross__13027",
            "UPMC for Life__13046",
            "Humana__13116",
            "UnitedHealthcare__13120",
            "Medica__13212",
            "Aetna Medicare__13231",
            "Wellcare by Allwell__13300",
            "Anthem Blue Cross and Blue Shield__13302",
            "My Choice Wisconsin Health Plan__13305",
            "Community Care__13307",
            "Dean Health Plan, Inc.__13311",
            "Quartz Medicare Advantage__13314",
            "HealthPartners__13316",
            "Humana__13318",
            "iCare__13320",
            "Medica__13322",
            "Medical Associates Clinic Health Plan of Wisconsin__13323",
            "Network Health Medicare Advantage Plans__13324",
            "UnitedHealthcare__13326",
            "Security Health Plan of Wisconsin, Inc.__13356",
            "The Health Plan__13413",
            "Highmark Inc.__13415",
            "Humana__13418",
            "Community Health Plan of WA Medicare Advantage__13505",
            "Kaiser Permanente__13511",
            "Providence Medicare Advantage Plans__13521",
            "Regence BlueCross BlueShield of Oregon__13523",
            "Regence BlueShield Of Idaho__13524",
            "UnitedHealthcare__13527",
            "Blue Cross & Blue Shield of Rhode Island__13601",
            "UnitedHealthcare__13614",
            "Humana__13714",
            "BlueCross BlueShield of New Mexico__13715",
            "Presbyterian Health Plan__13720",
            "UnitedHealthcare__13722",
            "Medica__13814",
            "Aetna Medicare__13900",
            "Martins Point Generations Advantage__13914",
            "Humana__13916",
            "UnitedHealthcare__13918",
            "Wellcare__13924",
            "Anthem Blue Cross and Blue Shield__14002",
            "Humana__14012",
            "UnitedHealthcare__14017",
            "Aetna Medicare__14101",
            "Anthem Blue Cross and Blue Shield__14102",
            "ConnectiCare__14106",
            "UnitedHealthcare__14117",
            "Wellcare__14127",
            "Arkansas Blue Medicare__14202",
            "UnitedHealthcare__14205",
            "Humana__14215",
            "Aetna Medicare__14218",
            "UnitedHealthcare__14310",
            "Kaiser Permanente__14316",
            "Blue Cross and Blue Shield of Minnesota__14401",
            "Blue Plus__14404",
            "HealthPartners__14411",
            "Medica__14419",
            "PrimeWest Health__14423",
            "South Country Health Alliance__14427",
            "UCare__14430",
            "AlohaCare__14501",
            "Kaiser Permanente__14513",
            "UnitedHealthcare__14516",
            "HMSA Akamai Advantage__14541",
            "Blue Cross Blue Shield of Massachusetts__14602",
            "Commonwealth Care Alliance, Inc.__14607",
            "UnitedHealthcare__14610",
            "Fallon Health__14612",
            "Health New England Medicare Advantage Plans__14616",
            "Molina Healthcare__14623",
            "Tufts Health Plan__14626",
            "Aetna Medicare__14700",
            "Wellpoint__14703",
            "UnitedHealthcare__14710",
            "Horizon Blue Cross Blue Shield of New Jersey__14718",
            "Wellcare__14732",
            "UnitedHealthcare__14915",
            "Aetna Medicare__15000",
            "EmblemHealth Medicare HMO__15002",
            "CDPHP Medicare Advantage__15007",
            "Elderplan__15012",
            "Anthem Blue Cross and Blue Shield__15014",
            "Excellus Health Plan, Inc__15017",
            "Healthfirst Medicare Plan__15028",
            "Humana__15034",
            "Independent Health__15035",
            "MetroPlus Health Plan__15038",
            "MVP HEALTH CARE__15039",
            "Humana__15040",
            "UnitedHealthcare__15043",
            "Senior Whole Health of New York__15045",
            "Wellcare__15055",
            "VNSNY CHOICE Medicare__15056",
            "Highmark__15085",
            "Wellcare by Allwell__15094",
            "Wellcare__15099",
            "Wellcare__15112",
            "Anthem HealthKeepers__15120",
            "Wellcare__15145",
            "BlueCross BlueShield of Illinois__15147",
            "Quartz Medicare Advantage__15175",
            "Ultimate Health Plans__15185",
            "Clover Health__15194",
            "VISTA Health Plan Inc.__15195",
            "SelectHealth__15214",
            "BlueCross BlueShield of Texas__15250",
            "Aetna Medicare__15262",
            "Molina Healthcare of Utah & Idaho__15284",
            "Arkansas Blue Medicare__15398",
            "Anthem Blue Cross and Blue Shield__15410",
            "Molina Healthcare of Washington, Inc.__15423",
            "Sentara Medicare__15434",
            "Wellpoint__15438",
            "Aetna Medicare__15445",
            "BlueCross BlueShield of Oklahoma__15450",
            "BlueCare Plus Tennessee__15454",
            "Aspire Health Plan__15456",
            "Aetna Medicare__15472",
            "Aetna Medicare__15480",
            "Molina Healthcare of Illinois__15482",
            "Health Partners Medicare__15483",
            "Itasca Medical Care/IMCare Classic__15484",
            "Clear Spring Health__15486",
            "Banner Medicare Advantage Dual__15560",
            "UnitedHealthcare Community Plan__15569",
            "Memorial Hermann Health Plan__15581",
            "Wellcare By Buckeye Health Plan__15584",
            "Aetna Medicare__15593",
            "BlueCross BlueShield of Montana__15599",
            "Humana__15619",
            "CHRISTUS Health Plan Generations__15633",
            "Prominence Health Plan__15647",
            "Humana__15657",
            "Humana__15668",
            "Prominence Health Plan__15670",
            "Humana__15671",
            "Humana__15672",
            "IEHP DualChoice__15674",
            "Tribute Health Plans__15677",
            "Community Health Group__15693",
            "Aetna Medicare__15698",
            "CalOptima Health OneCare__15710",
            "CareFirst BlueCross BlueShield Medicare Advantage__15752",
            "Capital Blue Cross__15756",
            "Humana__15757",
            "Wellcare by Health Net__15758",
            "Aetna Medicare__15765",
            "Humana__15774",
            "Aetna Medicare__15789",
            "SummaCare Medicare Advantage Plans__15796",
            "Johns Hopkins HealthCare__15798",
            "HealthTeam Advantage__15809",
            "Passport Advantage__15812",
            "Dean Advantage Medicare Advantage__15823",
            "Medical Mutual of Ohio__15824",
            "UnitedHealthcare__15831",
            "UnitedHealthcare__15839",
            "Blue Cross and Blue Shield of Louisiana HMO__15844",
            "Molina Healthcare of South Carolina__15852",
            "Aetna Medicare__15853",
            "UnitedHealthcare__15863",
            "Humana__15900",
            "Humana__15903",
            "Aetna Medicare__15914",
            "Sharp Health Plan__15921",
            "Wellcare__15932",
            "Aetna Medicare__15937",
            "Wellcare__15941",
            "UnitedHealthcare__15953",
            "Wellcare__15962",
            "UnitedHealthcare__15964",
            "Aetna Medicare__15969",
            "Clear Spring Health__15977",
            "UPMC for Life__15981",
            "Lifeworks Advantage__15995",
            "Blue Cross and Blue Shield of Nebraska__15997",
            "UnitedHealthcare__15998",
            "Wellcare__15999",
            "Wellcare__16001",
            "Aetna Medicare__16007",
            "Aetna Medicare__16009",
            "CareSource__16016",
            "Aetna Medicare__16022",
            "VillageCareMAX__16032",
            "Provider Partners Health Plans__16035",
            "American Health Advantage of Missouri__16057",
            "Provider Partners Health Plans__16059",
            "PruittHealth Premier__16060",
            "RiverSpring Health Plans__16061",
            "American Health Advantage of Oklahoma__16066",
            "AgeRight Advantage__16067",
            "AHF__16068",
            "VillageHealth__16069",
            "West Virginia Senior Advantage__16071",
            "Signature Advantage (HMO SNP)__16075",
            "NHC Advantage__16082",
            "NEIGHBORHOOD HEALTH PLAN OF RHODE ISLAND__16090",
            "SIMPRA Advantage__16098",
            "Wellcare by Allwell__16101",
            "Humana__16102",
            "HealthSpring__16108",
            "Imperial Health Plan of California, Inc.__16112",
            "Anthem Blue Cross and Blue Shield__16114",
            "HealthSpring__16119",
            "HealthSpring__16123",
            "HealthSpring__16127",
            "HealthSpring__16134",
            "CLOVER HEALTH__16135",
            "Humana__16145",
            "Humana__16146",
            "Molina Healthcare of Utah & Idaho__16147",
            "HealthSpring__16150",
            "Wellcare__16156",
            "Wellcare__16161",
            "HealthSpring__16163",
            "Kansas Health Advantage__16165",
            "Humana__16169",
            "Humana__16173",
            "Aetna Medicare__16176",
            "HealthSpring__16178",
            "Humana__16179",
            "Blue Cross Blue Shield of Minnesota__16188",
            "Wellcare by Allwell__16193",
            "HealthSpring__16196",
            "HealthSpring__16200",
            "Humana__16202",
            "PacificSource Medicare__16205",
            "HealthSpring__16207",
            "Humana__16209",
            "Liberty Medicare Advantage__16210",
            "Wellcare__16212",
            "Great Plains Medicare Advantage__16215",
            "Great Plains Medicare Advantage__16220",
            "Humana__16224",
            "Humana__16230",
            "Humana__16231",
            "Humana__16244",
            "Wellcare__16247",
            "Humana__16248",
            "Wellcare__16251",
            "HealthSpring__16252",
            "CLOVER HEALTH__16253",
            "Humana__16262",
            "Blue Cross Blue Shield of South Carolina__16263",
            "HealthSpring__16264",
            "Great Plains Medicare Advantage__16268",
            "Humana__16273",
            "CHRISTUS Health Plan Generations__16276",
            "HealthSpring__16277",
            "CLOVER HEALTH__16279",
            "Humana__16282",
            "Humana__16285",
            "Humana__16294",
            "Wellcare__16295",
            "Humana__16296",
            "UnitedHealthcare__16302",
            "Clear Spring Health__16333",
            "CarePartners of Connecticut__16337",
            "BayCare Health Plans__16346",
            "Devoted Health__16347",
            "Doctors HealthCare Plans, Inc.__16348",
            "Solis Health Plans__16351",
            "Medica__16357",
            "Longevity Health__16369",
            "Blue Cross and Blue Shield of Louisiana__16391",
            "Allina Health Aetna Medicare__16410",
            "UnitedHealthcare__16414",
            "PruittHealth Premier__16432",
            "Medica__16438",
            "Aetna Medicare__16442",
            "Humana__16447",
            "SelectHealth__16456",
            "Longevity Health Plan__16462",
            "Nascentia Health Plus__16463",
            "Valor Health Plan__16470",
            "Aetna Medicare__16476",
            "Aetna Medicare__16483",
            "Clover Health__16486",
            "PruittHealth Premier__16487",
            "American Health Advantage of Tennessee__16494",
            "NHC Advantage__16500",
            "Wellpoint__16504",
            "HealthSpring__16543",
            "HealthSpring__16544",
            "Molina Healthcare of Arizona__16562",
            "HealthSpring__16573",
            "Longevity Health Plan__16586",
            "Georgia Health Advantage__16591",
            "Saint Alphonsus Health Plan__16601",
            "Provider Partners Health Plans__16610",
            "Zing Health__16611",
            "Blue Cross and Blue Shield of Kansas__16615",
            "American Health Advantage of Louisiana__16622",
            "Healthy Blue__16623",
            "KeyCare Advantage__16627",
            "Anthem Maine Health__16629",
            "Align Senior Care__16631",
            "Wellcare__16640",
            "American Health Advantage of MS__16642",
            "Experience Health, Inc.__16650",
            "Troy Medicare__16651",
            "UnitedHealthcare__16655",
            "HealthSpring__16663",
            "Longevity Health Plan__16665",
            "Wellcare__16670",
            "Hamaspik, Inc.__16674",
            "UnitedHealthcare__16703",
            "HealthSpring__16707",
            "Community Health Choice__16713",
            "Devoted Health__16714",
            "El Paso Health Advantage Dual SNP__16715",
            "ProCare Advantage__16718",
            "Texas Independence Health Plan__16719",
            "Wellcare__16734",
            "Aetna Medicare__16744",
            "Humana__16752",
            "Humana__16755",
            "Align Senior Care__16758",
            "Alignment Health Plan__16759",
            "Alignment Health Plan__16760",
            "American Health Advantage of Florida__16762",
            "American Health Advantage of Texas__16763",
            "American Health Advantage of Utah__16764",
            "Wellpoint__16765",
            "Wellpoint__16767",
            "Aspirus Health Plan__16777",
            "Astiva Health__16778",
            "Banner Medicare Advantage Prime__16780",
            "Braven Health__16785",
            "CareSource__16788",
            "HealthSpring__16797",
            "HealthSpring__16798",
            "HealthSpring__16799",
            "HealthSpring__16802",
            "HealthSpring__16803",
            "Clever Care Health Plan__16804",
            "Communicare Advantage__16807",
            "Communicare Advantage__16808",
            "Community First Health Plans__16809",
            "Devoted Health__16810",
            "Devoted Health__16811",
            "Wellcare by Fidelis Care__16815",
            "Group Health Cooperative of Eau Claire__16816",
            "Longevity Health Plan__16834",
            "Longevity Health Plan__16835",
            "Longevity Health Plan__16836",
            "MediGold__16838",
            "MyTruAdvantage__16841",
            "NextBlue of North Dakota__16842",
            "NHC Advantage__16843",
            "Perennial Advantage__16847",
            "Perennial Advantage__16848",
            "Prominence Health Plan__16850",
            "Provider Partners Health Plans__16851",
            "Zing Health__16896",
            "Zing Health__16897",
            "Humana__16957",
            "Align Senior Care__16973",
            "Alignment Health Plan__16974",
            "Alterwood Advantage__16975",
            "American Health Advantage of Utah__16976",
            "Wellcare by Allwell__16979",
            "WellSense Health Plan__16988",
            "Mass Advantage__16991",
            "HealthSpring__16992",
            "HealthSpring__16993",
            "HealthSpring__16994",
            "Devoted Health__16999",
            "eternalHealth__17000",
            "First Choice VIP Care__17005",
            "Florida Complete Care__17006",
            "Highmark Inc.__17010",
            "Leon Health Plans__17017",
            "McLaren Medicare__17018",
            "Trinity Health Plan New York__17025",
            "Provider Partners Health Plans__17031",
            "Align powered by Sanford Health Plan__17032",
            "Align powered by Sanford Health Plan__17033",
            "Align powered by Sanford Health Plan__17034",
            "Wellcare__17037",
            "Wellcare__17038",
            "Wellmark Advantage Health Plan__17050",
            "Wellmark Advantage Health Plan__17051",
            "Anthem MyCare Ohio__17065",
            "SCAN Health Plan__17066",
            "Humana__17076",
            "Humana__17077",
            "Humana__17078",
            "Humana__17079",
            "Humana__17080",
            "Humana__17081",
            "Humana__17082",
            "Humana__17083",
            "Humana__17084",
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
   $agentId = 246;
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
