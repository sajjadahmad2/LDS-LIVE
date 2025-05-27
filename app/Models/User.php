<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

     public function ghlauth()
     {
         return $this->hasMany(GhlAuth::class, 'user_id');
     }
     public function ghl()
     {
         return $this->hasMany(GhlAuth::class, 'company_id', 'company_id');
     }
    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

}
