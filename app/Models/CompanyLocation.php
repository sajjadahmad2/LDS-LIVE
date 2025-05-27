<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class CompanyLocation extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $table='company_locations';
    protected $fillable = [
        'location_id',   // Primary identifier for the location
        'company_id',    // Foreign key for the company
        'location_name', // Name of the location
        'location_email',// Email associated with the location
        'user_id',       // User ID (to track who created the record)
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
