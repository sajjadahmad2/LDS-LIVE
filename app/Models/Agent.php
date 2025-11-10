<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'destination_location', 'destination_webhook',
        'consent', 'priority', 'daily_limit',
        'monthly_limit', 'weightage', 'user_id', 'location_id', 'total_limit', 'npm_number',
        'cross_link', 'agent_count_weightage',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function states()
    {
        return $this->hasMany(AgentState::class, 'agent_id');
    }

    public function carrierTypes()
    {
        return $this->hasMany(AgentCarrierType::class, 'agent_id');
    }

    public function agentLeadTypes()
    {
        return $this->hasMany(AgentLeadType::class, 'agent_id');
    }
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_agents');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'agent_id');
    }
}
