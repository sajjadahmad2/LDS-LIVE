<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\State;
use carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        ini_set('memory_limit', '-1');

        if ($request->ajax()) {
            try {
                $query = Contact::query()
                    ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'city', 'postal_code', 'state', 'full_address', 'created_at', 'agent_id', 'campaign_id'])
                    ->with([
                        'agent:id,name',
                        'campaign:id,campaign_name',
                    ])
                    ->where('status', 'Sent')
                    ->orderByDesc('id');

                // ✅ Filter: Agent
                if ($request->filled('agent_ids') && $request->agent_ids !== 'all') {
                    $query->where('agent_id', $request->agent_ids);
                }

                // ✅ Filter: State
                if ($request->filled('state_ids') && $request->state_ids !== 'all') {
                    $states = State::where('id', $request->state_ids)->pluck('state');
                    $query->whereIn('state', $states);
                }

                // ✅ Filter: Campaign
                if ($request->filled('campaign_ids') && $request->campaign_ids !== 'all') {
                    $query->whereIn('campaign_id', [$request->campaign_ids]);
                }

                // ✅ Filter: Date Range
                if ($request->filled('customDateRange') && $request->customDateRange !== 'all') {
                    $dates = explode(' to ', $request->customDateRange);
                    if (count($dates) === 2) {
                        $start = Carbon::createFromFormat('Y-m-d', trim($dates[0]), 'America/Chicago')->startOfDay();
                        $end   = Carbon::createFromFormat('Y-m-d', trim($dates[1]), 'America/Chicago')->endOfDay();
                        $query->whereBetween('created_at', [$start, $end]);
                    }
                }

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->editColumn('agent_id', fn($row) => $row->agent->name ?? '')
                    ->editColumn('campaign_id', fn($row) => $row->campaign->campaign_name ?? '')
                    ->editColumn('first_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                    ->filter(function ($query) use ($request) {
                        $search = $request->search['value'] ?? '';
                        if (! empty($search)) {
                            $query->where(function ($q) use ($search) {
                                $q->where('contacts.id', 'LIKE', "%{$search}%")
                                    ->orWhere('contacts.first_name', 'LIKE', "%{$search}%")
                                    ->orWhere('contacts.last_name', 'LIKE', "%{$search}%")
                                    ->orWhere('contacts.contact_id', 'LIKE', "%{$search}%")
                                    ->orWhere('contacts.email', 'LIKE', "%{$search}%")
                                    ->orWhere('contacts.phone', 'LIKE', "%{$search}%")
                                    ->orWhere('contacts.state', 'LIKE', "%{$search}%")
                                    ->orWhereHas('agent', fn($q2) => $q2->where('name', 'LIKE', "%{$search}%"))
                                    ->orWhereHas('campaign', fn($q3) => $q3->where('campaign_name', 'LIKE', "%{$search}%"));
                            });
                        }
                    })
                    ->make(true);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
            }
        }

        return view('admin.contact.index');
    }

}
