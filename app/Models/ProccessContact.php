<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use DateTimeZone;
class ProccessContact extends Model
{
    use HasFactory;
      // Automatically adjust created_at and updated_at to CDT/CST
    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $timezone = self::getCentralTimezone();
            $now = new DateTime('now', new DateTimeZone($timezone));
            if (empty($model->created_at)) {
                $model->created_at = $now->format('Y-m-d H:i:s');
            }
            $model->updated_at = $now->format('Y-m-d H:i:s');
        });
    }
    /**
     * Determine if current date is in CDT or CST.
     *
     * @return string Timezone (CDT or CST)
     */
    public static function getCentralTimezone()
    {
        $now = new DateTime('now', new DateTimeZone('America/Chicago'));
        if ($now->format('I')) { // DST is active
            return 'America/Chicago'; // Automatically resolves to CDT
        }
        return 'America/Chicago'; // Automatically resolves to CST
    }
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
    private function convertToCentralTime($value)
    {
        $datetime = new DateTime($value, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone(self::getCentralTimezone()));
        return $datetime->format('Y-m-d H:i:s');
    }
    protected $guarded =[];
}
