<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCarrierType extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'carrier_type',
    ];

    /**
     * Define the relationship with the Agent model.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

}

