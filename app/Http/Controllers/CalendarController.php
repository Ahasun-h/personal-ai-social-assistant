<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Schedule;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $schedules = Schedule::query()
            ->with(['draft.versions', 'draft.postMedia.asset', 'socialAccount'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $accounts = SocialAccount::where('is_active', true)->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'schedules' => $schedules,
                    'accounts' => $accounts,
                ],
            ]);
        }

        return Inertia::render('Calendar/Index', [
            'schedules' => $schedules,
            'accounts' => $accounts,
        ]);
    }

    public function reschedule(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $schedule = Schedule::findOrFail($id);
        $schedule->update([
            'scheduled_at' => $validated['scheduled_at'],
            'status' => Schedule::STATUS_QUEUED,
        ]);

        AuditLog::record('reschedule_post', $schedule, null, null, [
            'new_scheduled_at' => $validated['scheduled_at'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $schedule,
        ]);
    }
}
