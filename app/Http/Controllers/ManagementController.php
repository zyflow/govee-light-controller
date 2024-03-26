<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ManagementController extends Controller
{
    public function index() {
        return Artisan::call('schedule:run');
    }
}
