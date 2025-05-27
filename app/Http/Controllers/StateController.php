<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use DataTables;

class StateController extends Controller
{
    /**
     * Display a listing of the states.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = State::all();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="row row-cols-auto g-3">';

                    // Edit button
                    $btn .= '<div class="col">';
                    $btn .= '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stateModal" onclick="savaStateData(' . $row->id . ', \'' . $row->state . '\', \'' . $row->location_id . '\')" class="btn btn-outline-primary"><i class="bx bx-edit py-2"></i></button>';
                    $btn .= '</div>';

                    // Delete button
                    $btn .= '<div class="col">';
                    $btn .= '<a href="javascript:void(0);" class="btn btn-outline-danger confirm-delete" data-id="' . $row->id . '" onclick="deleteState(' . $row->id . ')"><i class="bx bx-trash py-2"></i></a>';
                    $btn .= '</div>';

                    $btn .= '</div>';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.states.index');
    }

    /**
     * Store a newly created state in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'state' => 'required|string|max:255',
            'location_id' => 'required|string|max:255',
        ]);

        State::create([
            'state' => $validated['state'],
            'location_id' => $validated['location_id'],
            'user_id' => auth()->id(), // Automatically assigns the current user
        ]);

        return response()->json(['success' => 'State created successfully!']);
    }

    /**
     * Update the specified state in storage.
     */
    public function update(Request $request, $id)
    {
        $state = State::findOrFail($id);

        $validated = $request->validate([
            'state' => 'required|string|max:255',
            'location_id' => 'required|string|max:255',
        ]);

        $state->update([
            'state' => $validated['state'],
            'location_id' => $validated['location_id'],
        ]);

        return response()->json(['success' => 'State updated successfully!']);
    }

    /**
     * Remove the specified state from storage.
     */
    public function destroy($id)
    {
        $state = State::findOrFail($id);
        $state->delete();

        return response()->json(['success' => 'State deleted successfully!']);
    }

    public function saveOrUpdateState(Request $request)
    {
       // dd("404");
        // Get the state data from the request body
        $stateData = \state();
//dd($stateData);
        // Call the helper function to save or update states
        return \saveOrUpdateStateInDatabase($stateData);
    }
    public function searchStateByAjax(Request $request){
        $term = $request->q ?? ($request->term ?? ($request->search ?? ''));
        $data = State::query()->select('state as text', 'id');
        if (!empty($term)) {
            $data->where(function ($query) use ($term) {
                $query->where('state', 'LIKE', '%' . $term . '%')->orWhere('id', $term);
            });
        }
        $results = $data->take(100)->get();
        return response()->json($results);
    }

}
