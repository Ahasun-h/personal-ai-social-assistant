<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => [
                'interest_keywords' => $settings['interest_keywords'] ?? ['AI Agents', 'Laravel 12', 'Autonomous SDLC', 'DevOps'],
                'banned_words' => $settings['banned_words'] ?? ['synergy', 'delve', 'game-changer', 'testament to'],
                'brand_tone' => $settings['brand_tone'] ?? 'Pragmatic, technical, high-signal, no marketing fluff',
                'monthly_budget_cap_usd' => $settings['monthly_budget_cap_usd'] ?? 50.00,
                'daily_budget_cap_usd' => $settings['daily_budget_cap_usd'] ?? 5.00,
                'preferred_posting_slots' => $settings['preferred_posting_slots'] ?? ['08:30', '11:00', '14:30', '18:00'],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = User::first();
        $input = $request->all();

        foreach ($input as $k => $v) {
            Setting::set($user->id, $k, $v);
        }

        AuditLog::record('update_settings', $user, $user->id, null, $input);

        return response()->json([
            'success' => true,
            'data' => $input,
        ]);
    }
}
