<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Schedule;
use App\Models\Draft;
use App\Models\User;

class CalendarController extends Controller
{
    public function index(): JsonResponse
    {
        $schedules = Schedule::with(['draft.versions', 'socialAccount'])
            ->orderBy('scheduled_for', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_id' => 'required|uuid',
            'social_account_id' => 'required|uuid',
            'scheduled_for' => 'required|date',
        ]);

        $user = User::first();

        $schedule = Schedule::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'draft_id' => $validated['draft_id'],
            'social_account_id' => $validated['social_account_id'],
            'scheduled_for' => $validated['scheduled_for'],
            'status' => 'pending',
        ]);

        $draft = Draft::find($validated['draft_id']);
        if ($draft) {
            $draft->update([
                'status' => Draft::STATUS_SCHEDULED,
                'scheduled_at' => $validated['scheduled_for'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule->load(['draft', 'socialAccount']),
        ], 201);
    }
}
