<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\DashboardStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $stats): View
    {
        return view('dashboard', [
            'stats' => $stats->getOverview(),
            'unreadMessagesCount' => ContactMessage::whereNull('read_at')->count(),
            'recentMessages' => ContactMessage::query()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }
}