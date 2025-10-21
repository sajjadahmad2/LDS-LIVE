<?php

namespace App\Jobs;

use App\Models\CompanyLocation;
use App\Models\GhlAuth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class ConnectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $company_id;
    protected $loc;

    /**
     * Create a new job instance.
     */
    public function __construct($company_id, $loc)
    {
        $this->company_id = $company_id;
        $this->loc = $loc;

    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $getalllocations = \CRM::agencyV2($this->company_id, 'locations/search?limit=1000');

        if ($getalllocations && property_exists($getalllocations, 'locations')) {
            $locations = $getalllocations->locations;

            if (! empty($locations)) {
                foreach ($locations as $location) {
                    CompanyLocation::updateOrCreate(
                        [
                            'location_id' => $location->id ?? null,
                        ],
                        [
                            'user_id'        => $this->company_id,
                            'location_email' => $location->email ?? null,
                            'location_name'  => $location->name ?? null,
                            'company_id'     => $location->companyId ?? null,
                        ]
                    );

                    $locationId = \CRM::connectLocation($this->company_id, $location->id, $this->loc, $this->company_id);

                    if (isset($locationId->location_id)) {
                        if ($locationId->statusCode == 400) {
                            \Log::error('Bad Request: Invalid locationId or accessToken', [
                                'location_id' => $locationId->location_id ?? null,
                                'user_id'     => $this->company_id ?? null,
                                'response'    => $locationId,
                            ]);
                            continue;
                        }


                    }
                }
            }
        }
        return true;
    }
}
