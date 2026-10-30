<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Schedule;
use App\Models\SocialAccount;

class CalendarController extends Controller
{
    public function index(): Response
    {
        $schedules = Schedule::query()
            ->with(['draft.versions', 'draft.postMedia.mediaAsset', 'socialAccount'])
            ->orderBy('scheduled_for', 'asc')
            ->get();

        $accounts = SocialAccount::where('is_active', true)->get();

        return Inertia::render('Calendar/Index', [
            'schedules' => $schedules,
            'accounts' => $accounts,
        ]);
    }
}
