<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'session_timeout_minutes' => Setting::getSessionTimeoutMinutes(),
            'session_lockout_minutes' => Setting::getValue('session_lockout_minutes', Setting::getSessionTimeoutMinutes()),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'session_timeout_minutes' => 'required|integer|min:1|max:60',
            'session_lockout_minutes' => 'required|integer|min:1|max:60',
        ]);

        Setting::setValue('session_timeout_minutes', $request->session_timeout_minutes, 'integer', 'Session timeout in minutes for inactivity');
        Setting::setValue('session_lockout_minutes', $request->session_lockout_minutes, 'integer', 'Session lockout in minutes for screen lock');

        // Clear any cached settings
        Cache::forget('settings.session_timeout_minutes');
        Cache::forget('settings.session_lockout_minutes');

        return back()->with('success', 'Settings updated successfully!');
    }
}
