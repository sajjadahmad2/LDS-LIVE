<?php
namespace App\Jobs;

use App\Models\Agent;
use App\Models\CampaignAgent;
use App\Models\ProccessContact;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessNewTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $request;
    protected $camid;

    /**
     * Create a new job instance.
     */
    public function __construct($request, $camid = null)
    {
        $this->request = $request;
        $this->camid   = $camid;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $data        = $this->request;
        $contact_id  = $data['contact_id'] ?? null;
        $location_id = $data['location']['id'] ?? null;
        $email       = $data['email'] ?? null;

        if (is_null($this->camid)) {
            Log::info("Webhook Campaign id not found: {$this->camid}");
            return;
        }

        if (! is_null($email)) {
            $proccessContact = ProccessContact::where('email', $data['email'])->first();

            $contactData = [
                'first_name'   => $data['first_name'] ?? null,
                'last_name'    => $data['last_name'] ?? null,
                'email'        => $data['email'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'address1'     => $data['address1'] ?? null,
                'tags'         => isset($data['tags']) ? json_encode($data['tags']) : null,
                'full_address' => $data['full_address'] ?? null,
                'country'      => $data['country'] ?? null,
                'source'       => $data['contact_source'] ?? null,
                'date_added'   => isset($data['date_created']) ? Carbon::parse($data['date_created']) : null,
                'city'         => $data['city'] ?? null,
                'state'        => $data['state'] ?? null,
                'postal_code'  => $data['postal_code'] ?? null,
                'location_id'  => $location_id,
                'contact_id'   => $contact_id ?? null,
                'location'     => isset($data['location']) ? json_encode($data['location']) : null,
                'address'      => $data['location']['fullAddress'] ?? null,
                'status'       => 'In Complete',
            ];

            if ($proccessContact) {
                $proccessContact->update($contactData);
                Log::info("Updated contact from Webhook contact ID: {$contact_id}");
            } else {
                $proccessContact = ProccessContact::create($contactData);
                Log::info("Created new contact from webhook contact Email: {$email}");
            }

            // Call the findAgent method
            $this->findAgent($proccessContact, $this->camid);
        } else {
            Log::info("Webhook email not found");
        }
    }

    /**
     * Find and assign an agent.
     */
    protected function findAgent($proccessContact, $camid = null)
    {
        $state      = $proccessContact->state;
        $contact_id = $proccessContact->contact_id;
        Log::info('Agent Find for ' . $state . ' and Campaign ' . $camid);

        if (! is_null($state)) {
            $currentMonth = Carbon::now('America/Chicago')->month;
            $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
            $agentIds     = CampaignAgent::where('campaign_id', $camid)->pluck('agent_id')->toArray();

            $agents = Agent::whereHas('states', function ($query) use ($state) {
                $query->where(DB::raw('TRIM(LOWER(state))'), $state)
                    ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
            })
                ->whereIn('id', $agentIds)
                ->withCount([
                    'contacts as monthly_contacts_count' => function ($query) use ($currentMonth) {
                        $query->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                    },
                    'contacts as daily_contacts_count'   => function ($query) use ($currentDate) {
                        $query->where('status', 'Sent')->whereDate('created_at', $currentDate);
                    },
                    'contacts as total_contacts_count'   => function ($query) {
                        $query->where('status', 'Sent');
                    },
                ])
                ->orderBy('priority', 'asc')
                ->orderByDesc('weightage')
                ->get();

            $groupedAgents = $agents->groupBy('priority');
            $agentIds      = $groupedAgents->map(fn($group) => $group->pluck('id')->toArray());

            Log::info('Agents available for Contact ID ' . $contact_id . ': ' . json_encode($agentIds));

            foreach ($groupedAgents as $priority => $priorityAgents) {
                foreach ($priorityAgents as $agent) {
                    if ($agent->total_contacts_count < $agent->total_limit &&
                        $agent->monthly_contacts_count < $agent->monthly_limit &&
                        $agent->daily_contacts_count < $agent->daily_limit &&
                        $agent->agent_count_weightage < $agent->weightage) {

                        $proccessContact->agent_id = $agent->id;
                        $proccessContact->save();
                        Log::info('Agent assigned: ' . $agent->id);
                        $agent->increment('agent_count_weightage', 1);
                        return;
                    }
                }

                foreach ($priorityAgents as $agent) {
                    if ($agent->total_contacts_count < $agent->total_limit &&
                        $agent->monthly_contacts_count < $agent->monthly_limit &&
                        $agent->daily_contacts_count < $agent->daily_limit) {
                        $agent->update(['agent_count_weightage' => 0]);
                    }
                }

                foreach ($priorityAgents as $agent) {
                    if ($agent->total_contacts_count < $agent->total_limit &&
                        $agent->monthly_contacts_count < $agent->monthly_limit &&
                        $agent->daily_contacts_count < $agent->daily_limit) {

                        $proccessContact->agent_id = $agent->id;
                        $proccessContact->save();
                        Log::info('Agent re-assigned: ' . $agent->id);
                        $agent->increment('agent_count_weightage', 1);
                        return;
                    }
                }
            }
        }
    }

}
