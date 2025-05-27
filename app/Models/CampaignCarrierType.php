<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignCarrierType extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'carrier_type',
    ];

    /**
     * Define the relationship with the Campaign model.
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}

