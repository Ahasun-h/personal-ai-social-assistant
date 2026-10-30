<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\User;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->keyBy('key');
        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = User::first();
        $payload = $request->all();

        foreach ($payload as $key => $val) {
            Setting::updateOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                ['value' => $val]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => Setting::all()->keyBy('key'),
        ]);
    }
}

