<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadType extends Model
{
    use HasFactory;
    protected $table   = 'lead_types';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function agentLeadTypes()
    {
        return $this->hasMany(AgentLeadType::class, 'lead_type');
    }

    public function agentStates()
    {
        return $this->hasMany(AgentState::class, 'lead_type');
    }

    public function agentCarrierTypes()
    {
        return $this->hasMany(AgentCarrierType::class, 'lead_type');
    }

}
