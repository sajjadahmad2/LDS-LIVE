<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentLeadType extends Model
{
    use HasFactory;
    protected $table = 'agent_lead_types';
    protected $guarded = [];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
    public function leadType()
    {
        return $this->belongsTo(LeadType::class, 'lead_type');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
