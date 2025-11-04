<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use App\Models\GhlAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    protected $validation = [
        'name'  => 'required',
        'email' => 'required|email|unique:users',
    ];

    protected $route = 'admin.user';
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::select('*')->where('role', 1)->orWhere('from_agents', 1);
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#userModal"
                                           onclick="savaData(\'' . $row->id . '\', \'' . $row->name . '\', \'' . $row->email . '\', \'' . $row->password . '\', \'' . $row->role . '\', \'' . $row->location_id . '\')">
                                           <i class="bx bx-edit"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a href="' . route($this->route . '.autoLogin', $row->id) . '" class="dropdown-item">
                                            <i class="bx bx-user"></i> Auto Login
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item confirm-delete" data-id="' . $row->id . '">
                                            <i class="bx bx-trash"></i> Delete
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item status_changes" data-status="' . $row->id . '">
                                            <i class="bx bxs-low-vision"></i> Change Status
                                        </a>
                                    </li>
                                </ul>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.user.index');
    }

    public function store(Request $req)
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'location_id' => 'required|string|max:255',
        ];
        if ($req->id === '0') {
            $rules['password'] = 'required|string|min:8';
        } else {

        }
        $validatedData = $req->validate($rules);
        if (! empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }
        if ($req->id === '0') {
            $validatedData['role']     = is_role() == 'superadmin' ? 1 : 0;
            $validatedData['added_by'] = auth()->id();
        }
        $user    = User::updateOrCreate(['id' => $req->id], $validatedData);
        $user_id = $user->id;
        if ($user) {
            $login_user = loginUser();
            if ($req->id != '0') {
                GhlAuth::where('user_id', $req->id)->delete();
            }
            $token = GhlAuth::where('user_id', $login_user->id ?? '')->first();
            if (! empty($user) && isset($user)) {
                $locationId = \CRM::connectLocation($token->user_id, $user->location_id, $token);

                //dd($locationId);
                if (isset($locationId->location_id)) {
                    if ($locationId->statusCode == 400) {
                        \Log::error('Bad Request: Invalid locationId or accessToken', [
                            'location_id' => $user->location_id,
                            'user_id'     => $token->user_id,
                            'response'    => $locationId,
                        ]);
                        return response()->json(['error' => 'Invalid locationId or accessToken'], 400);
                    }

                    $ghl            = GhlAuth::where('location_id', $locationId->location_id)->first();
                    $locationDetail = \CRM::crmV2($token->user_id, 'locations/' . $ghl->location_id, 'get', '', [], false, $ghl->location_id, $ghl);

                    if (isset($locationDetail->location)) {
                        $subAccountDetail = $locationDetail->location;
                        $user             = User::find($user_id);
                        if ($user) {
                            $user->update([
                                'name'  => $subAccountDetail->name ?? $user->name,
                                'email' => $subAccountDetail->email ?? $user->email,
                            ]);
                        }
                    }
                    if ($ghl) {
                        $ghl->name    = $user->name;
                        $ghl->user_id = $user->id;
                        $ghl->save();
                        \Log::info('Updated GhlAuth record', [
                            'location_id' => $locationId->location_id,
                            'name'        => $user->name,
                        ]);
                    }
                    $apicall = \CRM::crmV2($user_id, 'customFields', 'get', '', [], false, $ghl->location_id, $ghl);
                    if (isset($apicall->customFields)) {
                        $apiData = $apicall->customFields;

                        foreach ($apiData as $field) {
                            $customFieldData = [
                                'cf_id'       => $field->id ?? null,
                                'cf_name'     => $field->name ?? null,
                                'cf_key'      => $field->fieldKey ?? null,
                                'dataType'    => $field->dataType ?? null,
                                'location_id' => $field->locationId ?? null,
                            ];
                            // Create a new CustomField record
                            $newCustomField = new CustomField();
                            foreach ($customFieldData as $key => $value) {
                                $newCustomField->$key = $value;
                            }

                            // Save the new custom field
                            $newCustomField->save();
                        }
                    }
                }
            }
        }
        if ($req->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => ' saved successfully',
                'data'    => $user,
            ]);
        }
    }
    public function profile()
    {
        $user = Auth::user();
        return view('admin.user.userProfile', get_defined_vars());
    }
    public function general(Request $req)
    {
        //dd($req->all());
        $user = Auth::user();
        $req->validate([
            'name' => 'required',
        ]);
        $user->name      = $req->name;
        $user->last_name = $req->lname;

        if ($req->hasFile('image')) {
            //dd("404");
            $user->image = uploadFile($req->image, 'uploads/profile', $req->name . '-' . $req->lname . '-' . time());
        }
        $save = $user->save();
        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function changePassword(Request $req)
    {
        $user  = Auth::user();
        $check = Validator::make($req->all(), [
            'current_password' => 'required|min:8',
            'password'         => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($check->fails()) {
            return redirect()->back()->with('error', $check->errors()->first());
        }

        $user->password = bcrypt($req->password);
        $user->save();

        return redirect()->back()->with('success', 'Password updated Successfully!');
    }

    public function changeEmail(Request $request)
    {
        $check = Validator::make($request->all(), [
            'email'    => 'required|email|unique:users,email,' . Auth::id(),
            'password' => 'required|min:8',
        ]);

        if ($check->fails()) {
            return redirect()->back()->with('error', $check->errors()->first());
        }

        $user        = Auth::user();
        $user->email = $request->email;
        $user->save();

        return redirect()->back()->with('success', 'Email updated Successfully!');
    }
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            GhlAuth::where('user_id', $user->id)->delete();
            if (! is_null($user->agent_id)) {
                \App\Models\Agent::where('id', $user->agent_id)->delete();
                \App\Models\AgentCarrierType::where('agent_id', $user->agent_id)->delete();
            }
            $user->delete();
            return response()->json(['success' => true, 'message' => 'User deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
        }
    }

    public function status($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->status = ! $user->status;
            $save->save();
            return response()->json(['succes' => true, "message" => "User status changed successfully"]);
        }
        return response()->json(['success' => true, "message" => "user not found"], 400);
    }
    public function userFetchAllLocation()
    {
        //dd("404");
        set_time_limit(0);
        $users = User::where('role', '!=', 0)->get();
        $token = GhlAuth::where('user_type', 'Company')->first();
        //dd($token);
        foreach ($users as $user) {
            $connectuid = $user->id;
            //GhlAuth::where('user_id', $user->id)->delete();
            $locationId = \CRM::connectLocation($token->user_id, $user->location_id, $token, $connectuid);

            if (isset($locationId->location_id)) {
                //$userId = User::where('id', $user->id)->first();
                $locationDetail = \CRM::crmV2($user->user_id, 'locations/' . $user->location_id, 'get', '', [], false, $token->location_id, $token);
                if (isset($locationDetail->location)) {
                    $subAccountDetail = $locationDetail->location;
                    $user             = User::find($user_id);
                    if ($user) {
                        $user->update([
                            'name'  => $subAccountDetail->name ?? $user->name,
                            'email' => $subAccountDetail->email ?? $user->email,
                        ]);
                    }
                }

                $userToken         = GhlAuth::where('user_id', $user->id)->first();
                $userTokenLocation = $userToken->location_id ?? null;
                //dd($userTokenLocation);
                if ($userToken) {
                    $userToken->name    = $user->name ?? null;
                    $userToken->user_id = $user->id ?? null;
                    $userToken->save();
                    \Log::info('Updated GhlAuth record', [
                        'location_id' => $locationId->location_id,
                        'name'        => $user->name,
                    ]);
                }
                $apicall = \CRM::crmV2($user->id, 'customFields?model=contact', 'get', '', [], false, $userTokenLocation, $userToken);
                if (isset($apicall->customFields)) {
                    $apiData = $apicall->customFields;
                    // if($locationId->location_id == 'ZIgOqABNFbyTL3XeKtXK'){
                    //     dd($apiData);
                    // }
                    if ($locationId->location_id == 'LkYQgCOcrUb4IaBeuQUZ') {
                        \Log::info('Count Custom Field', ['field' => $apiData, 'Count' => count($apiData)]);
                    }
                    foreach ($apiData as $field) {
                        // Find existing custom field record
                        $customField = CustomField::where('cf_id', $field->id)->where('location_id', $field->locationId)->first();
                        // Prepare data array with custom field values
                        if ($field->locationId == 'LkYQgCOcrUb4IaBeuQUZ' && strpos($field->name, 'By affixing your signature below, you hereby confirm that you have reviewed and understand the contents of the email regarding our health insurance policy recommendation for your Marketplace application. You further attest that the information you provided is accurate to the best of your knowledge. You understand that by signing this document, you are authorizing to submit your application for said health insurance.') !== false) {

                            \Log::info('Custom Field', ['field' => $field, 'Location_id' => $field->locationId]);
                        }
                        $customFieldData = [
                            'cf_id'       => $field->id ?? null,
                            'cf_name'     => $field->name ?? null,
                            'cf_key'      => $field->fieldKey ?? null,
                            'dataType'    => $field->dataType ?? null,
                            'location_id' => $field->locationId ?? null,
                        ];
                        if ($customField) {
                            foreach ($customFieldData as $key => $value) {
                                $customField->$key = $value;
                            }
                            $customField->save();
                        } else {
                            $customField = new CustomField();
                            foreach ($customFieldData as $key => $value) {
                                $customField->$key = $value;
                            }
                            $customField->save();
                        }
                    }
                }
            }
        }
        return response()->json(['data' => $user]);
    }
    public function syncCustomFieldsByLocation(Request $request)
    {
        $request->validate([
            'location_id' => 'required|string',
        ]);

        set_time_limit(0);

        $locationId = $request->location_id;
        $location   = $request->location_id;
        $agenttoken = GhlAuth::where('user_id', login_id())->where('user_type', 'Location')->where('location_id', $location)->first();
        $usertoken  = GhlAuth::where('user_id',  login_id())->where('user_type', 'Company')->first();


        // Start Database Transaction
        DB::beginTransaction();
        try {
            $locationId = \CRM::connectLocation($usertoken->user_id, $location, $usertoken);

            if (isset($locationId->location_id)) {
                if ($locationId->statusCode == 400) {
                    \Log::error('Bad Request: Invalid locationId or accessToken', [
                        'location_id' => $location,
                        'user_id'     => login_id(),
                        'response'    => $locationId,
                    ]);
                    return response()->json(['error' => 'Invalid locationId or accessToken'], 400);
                }

                $ghl            = GhlAuth::where('location_id', $locationId->location_id)->where('user_id', login_id())->first();
                $locationDetail = \CRM::crmV2(login_id(), 'locations/' . $ghl->location_id, 'get', '', [], false, $ghl->location_id, $ghl);
                if (isset($locationDetail->location)) {
                    $subAccountDetail = $locationDetail->location;
                }
                if ($subAccountDetail) {
                    $ghl->name = $subAccountDetail->location->name ?? '';
                    $ghl->save();
                    \Log::info('Updated GhlAuth record', [
                        'location_id' => $locationId->location_id,
                    ]);
                }
                $apicall = \CRM::crmV2(login_id(), 'customFields', 'get', '', [], false, $ghl->location_id, $ghl, login_id());
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
                        if ($customField) {
                            foreach ($customFieldData as $key => $value) {
                                $customField->$key = $value;
                            }
                            $customField->save();
                        } else {
                            $customField = new CustomField();
                            foreach ($customFieldData as $key => $value) {
                                $customField->$key = $value;
                            }
                            $customField->save();
                        }
                    }
                }

            }

            // ✅ Commit Transaction (Save Changes)
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Custom fields synced successfully']);
        } catch (\Exception $e) {
            // ❌ Rollback Transaction (Undo Changes)
            DB::rollBack();

            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
    public function updateCustomField(Request $request)
    {
        $request->validate([
            'customfield_id'   => 'required|string|exists:custom_fields,cf_id',
            'customfield_name' => 'required|string',
        ]);

        try {
            $customField = CustomField::where('cf_id', $request->customfield_id)->first();

            if (! $customField) {
                return response()->json(['message' => 'Custom field not found'], 404);
            }

            $customField->cf_name = $request->customfield_name;
            $customField->save();

            return response()->json(['message' => 'Custom field updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
