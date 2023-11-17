<?php

namespace App\Http\Controllers;

use App\Models\Sunset;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MainController extends Controller
{
    public function index() {
        $sunsets = \App\Models\Sunset::orderBy('id', 'desc')->first();

        $sunsetAt = null;
        if ($sunsets) {
            $sunsetAt = Carbon::parse($sunsets->sunset_at)->timezone('Europe/Riga')->format('H:i:s');
        }

        $sunsetAfter = Sunset::getMinutesUntilSunset();

        $govee = new GoveeController();
        $state = $govee->getState();
        $current_time = Carbon::now()->timezone('Europe/Riga')->format('H:i:s');

        return view('welcome', [
            'sunset' => $sunsetAfter,
            'sunset_at' => $sunsetAt,
            'currentlyLights' => $state,
            'current_time' => $current_time
        ]);
    }
}
