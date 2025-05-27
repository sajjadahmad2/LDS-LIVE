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
        'monthly_limit', 'weightage', 'user_id', 'location_id','total_limit','npm_number',
        'cross_link','agent_count_weightage'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function states()
    {
        return $this->belongsToMany(State::class, 'agent_states');
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaigns_agents');
    }
    public function carrierTypes()
    {
        return $this->hasMany(AgentCarrierType::class);
    }
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'agent_id');
    }
}
