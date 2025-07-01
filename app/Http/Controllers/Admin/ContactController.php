<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        ini_set('memory_limit', '-1');

        if ($request->ajax()) {
            try {
                $query = Contact::with([
        'agent:id,name',
        'campaign:id,campaign_name'
    ])
                    ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'state', 'created_at','contact_id', 'agent_id','campaign_id'])
                    ->where('status', 'Sent')
                    ->orderBy('id', 'desc');

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->editColumn('agent_id', function ($row) {
                        return $row->agent ? $row->agent->name : '';
                    })
                    ->editColumn('campaign_id', function ($row) {
                        return $row->campaign ? $row->campaign->campaign_name : '';
                    })
                    ->editColumn('first_name', function ($row) {
                        return $row->first_name . ' ' . $row->last_name;
                    })
                    ->filter(function ($query) use ($request) {
                        if (!empty($request->search['value'])) {
                            $searchValue = $request->search['value'];
                            $query->where(function ($q) use ($searchValue) {
                                $q->where('id', 'LIKE', "%{$searchValue}%")
                                  ->orWhere('first_name', 'LIKE', "%{$searchValue}%")
                                  ->orWhere('last_name', 'LIKE', "%{$searchValue}%")
                                  ->orWhere('contact_id', 'LIKE', "%{$searchValue}%")
                                  ->orWhere('email', 'LIKE', "%{$searchValue}%")
                                  ->orWhere('phone', 'LIKE', "%{$searchValue}%")
                                  ->orWhere('state', 'LIKE', "%{$searchValue}%")
                                  ->orWhereHas('agent', function ($subQuery) use ($searchValue) {
                                      $subQuery->where('name', 'LIKE', "%{$searchValue}%");
                                  })->orWhereHas('campaign', function ($subQuery) use ($searchValue) {
                                      $subQuery->where('campaign_name', 'LIKE', "%{$searchValue}%");
                                  });
                            });
                        }
                    })
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }
        }

        return view('admin.contact.index');
    }

}
