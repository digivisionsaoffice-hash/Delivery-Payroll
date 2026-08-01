<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $stats = [
            'employees' => Employee::count(),
            'platforms' => Platform::count(),
            'branches'  => Branch::count(),
        ];
        return view('settings.index', compact('stats'));
    }
}
