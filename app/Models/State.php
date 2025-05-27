<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = ['state', 'user_id', 'location_id','short_form'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agents()
    {
        return $this->hasManyThrough(Agent::class, AgentState::class, 'state_id', 'id', 'id', 'agent_id');
    }
}
