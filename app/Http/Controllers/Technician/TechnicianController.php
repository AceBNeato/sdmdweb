<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;

class TechnicianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:technician');
    }

    public function dashboard()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access the technician dashboard.');
        }

        // Get technician stats or data for dashboard
        $stats = [
            'total_equipment' => 0, // You can implement this based on your needs
            'recent_activities' => [],
            'pending_tasks' => []
        ];

        return view('technician.dashboard', compact('stats'));
    }

    /**
     * Display the technician profile via modal.
     */
    public function profile()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to view your profile.');
        }

        try {
            $user->loadMissing('office');

            $recentActivities = Activity::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            if ($recentActivities->isEmpty()) {
                Activity::create([
                    'user_id' => $user->id,
                    'action' => 'Account Created',
                    'description' => 'Technician account created in the system',
                ]);

                $recentActivities = Activity::where('user_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get();
            }

            if (request()->ajax() || request()->boolean('modal')) {
                return view('profile.show_modal', [
                    'user' => $user,
                    'recentActivities' => $recentActivities,
                ]);
            }

            return redirect()->route('technician.qr-scanner');
        } catch (\Exception $e) {
            Log::error('Technician profile error: ' . $e->getMessage(), [
                'technician_id' => $user->id ?? null,
            ]);

            return back()->with('error', 'Failed to load profile. Please try again.');
        }
    }

    /**
     * Display the technician edit profile modal.
     */
    public function editProfile()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to edit your profile.');
        }

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.edit_modal', [
                'technician' => $user,
            ]);
        }

        return redirect()->route('technician.qr-scanner');
    }
}
