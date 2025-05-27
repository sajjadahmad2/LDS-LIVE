<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_name', 'carrier_type', 'priority',
        'daily_limit', 'monthly_limit', 'weightage', 'user_id', 'location_id','total_limit'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agents()
    {
        return $this->belongsToMany(Agent::class, 'campaign_agents')->withPivot('weightage');

    }
    public function carrierTypes()
    {
        return $this->hasMany(CampaignCarrierType::class);
    }
    public function compaignAgents()
    {
        return $this->hasMany(CampaignAgent::class);
    }

    public function contacts()
    {
      return $this->hasMany(Contact::class, 'campaign_id');
    }
}
