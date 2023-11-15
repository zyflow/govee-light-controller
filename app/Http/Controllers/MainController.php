<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class MainController extends Controller
{
    public function index() {
        $sunsets = \App\Models\Sunset::orderBy('id', 'desc')->first();

        $sunsetAfter = null;
        $sunset = null;
        $sunsetAt = null;
        if ($sunsets) {
            $sunset = $sunsets->sunset_at;
            $now = Carbon::now()->format('H:i:s');
            $sunsetAfter = Carbon::parse($sunset)->shortAbsoluteDiffForHumans($now);
            $sunsetAt = Carbon::parse($sunsets->sunset_at)->timezone('Europe/Riga')->format('H:i:s');
        }

        $govee = new GoveeController();
        $state = $govee->getState();

        return view('welcome', ['sunset' => $sunsetAfter, 'sunset_at' => $sunsetAt, 'currentlyLights' => $state]);
    }
}
