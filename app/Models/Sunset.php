<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sunset extends Model
{
    use HasFactory;

    protected $fillable = [
        'sunset_at',
    ];


    public static function getMinutesUntilSunset($now = False): string
    {
        $lastSunset = Sunset::orderBy('id', 'desc')->first();
        $time = Carbon::parse($lastSunset->sunset_at)->timezone('Europe/Riga');

        if (!$now) {
            $now = Carbon::now();
        }

        $alteredDate = Carbon::parse($now)->setTime($time->hour, $time->minute, $time->second);

        if ($alteredDate->lt($now)) {
            $alteredDate->addDay();
        }

        return $alteredDate->shortAbsoluteDiffForHumans($now);
    }
}
