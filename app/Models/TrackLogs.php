<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_location',
        'campaign_id',
        'sent_to',
        'status',
        'reason',
    ];

    // Relationships
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'sent_to');
    }
}

