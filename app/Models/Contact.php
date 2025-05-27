<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use DateTimeZone;
class Contact extends Model
{
    use HasFactory;
  // Automatically adjust created_at and updated_at to CDT/CST
    /**
     * Determine if current date is in CDT or CST.
     *
     * @return string Timezone (CDT or CST)
     */
    /**
     * Get the created_at attribute in CDT/CST.
     *
     * @param string $value
     * @return string
     */
     public function getCreatedAtAttribute($value)
     {
         return \Carbon\Carbon::parse($value)->timezone('America/Chicago')->format('Y-m-d H:i:s');
     }
     public function getUpdatedAtAttribute($value)
     {
         return \Carbon\Carbon::parse($value)->timezone('America/Chicago')->format('Y-m-d H:i:s');
     }
    /**
     * Convert a UTC time to CDT/CST.
     *
     * @param string $value
     * @return string
     */
    protected $fillable = [
        'location_id',
        'contact_id',
        'address1',
        'city',
        'state',
        'company_name',
        'country',
        'source',
        'date_added',
        'date_of_birth',
        'dnd',
        'email',
        'name',
        'first_name',
        'last_name',
        'phone',
        'postal_code',
        'tags',
        'website',
        'attachments',
        'assigned_to',
        'custom_fields',
        'campaign_id',
        'agent_id',
        'user_id',
    ];
    protected $casts = [
        'attachments' => 'array',
        'custom_fields' => 'array',
    ];
    // Relationships
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}