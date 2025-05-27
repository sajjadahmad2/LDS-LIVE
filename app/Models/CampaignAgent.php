<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignAgent extends Model
{
    use HasFactory;

    protected $fillable = ['agent_id', 'campaign_id', 'user_id','weightage','priority'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
