<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
}
