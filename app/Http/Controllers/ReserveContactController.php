<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReserveContact;
use App\Models\Log;
use Yajra\DataTables\Facades\DataTables;

class ReserveContactController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = ReserveContact::select('id', 'phone', 'email', 'created_at', 'first_name', 'state')
                    ->where('status', 'Not Sent');

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('state', function ($row) {
                        // Get the agent(s) that match the status in ReserveContact
                        $agents = \App\Models\Agent::where('status', $row->state)->pluck('name', 'id');

                        // Create the dropdown options (just sending state ID for frontend to handle dropdown)
                        $dropdown = '<select class="form-control" name="state" id="state_' . $row->id . '">';
                        foreach ($agents as $id => $name) {
                            $selected = ($row->state == $id) ? 'selected' : ''; // Mark as selected if the state matches the agent id
                            $dropdown .= '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
                        }
                        $dropdown .= '</select>';

                        return $dropdown; 
                    })
                    ->addColumn('action', function ($row) {
                        $btn = '<div class="row row-cols-auto g-3">';
                        // Send button
                        $btn .= '<div class="col">';
                        $btn .= '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-placement="top" title="Edit User" data-bs-target="#userModal" onclick="savaData(\'' . $row->id . '\', \'' . $row->state . '\')">Send</button>';
                        $btn .= '</div>';
                        $btn .= '</div>';

                        return $btn; // Return the button HTML
                    })
                    ->rawColumns(['state', 'action']) // Ensure the 'state' and 'action' columns are treated as raw HTML
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }
        }

        return view('admin.reserve.index');
    }
    public function log(Request $request) {
        if ($request->ajax()) {
            try {
                $data = Log::select(['id', 'contact_id', 'name', 'email', 'state', 'reason', 'message'])->orderBy('id', 'desc');
                return DataTables::of($data)->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }
        }
        return view('admin.log');
    }
}
