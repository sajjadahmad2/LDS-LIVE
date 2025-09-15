<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentState extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'agent_states';
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class,'state_id');
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
