<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\State;
use App\Models\AgentState;
use App\Models\Campaign;
use App\Models\CampaignAgent;
use App\Models\Contact;
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
                $data = Contact::with([
                    'agent:id,name',
                    'campaign:id,campaign_name',
                ])

                    ->where('status', 'Sent')
                    ->orderBy('id', 'desc');
                if ($request->has('agent_ids') && $request->agent_ids !== 'all' && $request->agent_ids != '') {
                    $data->where('agent_id', $request->agent_ids);
                }
                if ($request->has('state_ids') && $request->state_ids !== 'all' && $request->state_ids != '') {
                    $agentState = State::where('id', $request->state_ids)->pluck('state')->toArray();

                    $data->whereIn('state', $agentState);
                }
                if ($request->has('campaign_ids') && $request->campaign_ids !== 'all' && $request->campaign_ids != '') {
                    $agentCampaign = Campaign::where('id', $request->campaign_ids)->pluck('id')->toArray();
                    \Log::info(["agentCampaign ids", $agentCampaign]);
                    $data->whereIn('campaign_id', $agentCampaign);
                }
                \Log::info(["Adent ID" => $request->agent_ids]);
                if ($request->has('customDateRange') && $request->customDateRange !== 'all' && $request->customDateRange != '') {
                    \Log::info('Custom Date Range: ' . $request->customDateRange);

                    $dates = explode(' to ', $request->customDateRange);

                    if (count($dates) == 2) {
                        $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]), 'America/Chicago')->startOfDay();
                        $endDate   = Carbon::createFromFormat('Y-m-d', trim($dates[1]), 'America/Chicago')->endOfDay();

                        \Log::info(["Start Date" => $startDate, "End Date" => $endDate]);

                        // No formatting here — use Carbon objects directly
                        $data->whereBetween('created_at', [$startDate, $endDate]);
                    }
                }

                return DataTables::of($data)
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
                        if (! empty($request->search['value'])) {
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
                    'error' => 'Something went wrong: ' . $e->getMessage(),
                ], 500);
            }
        }

        return view('admin.contact.index');
    }

}
