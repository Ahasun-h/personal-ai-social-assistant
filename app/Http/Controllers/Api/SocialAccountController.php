<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SocialAccount;

class SocialAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = SocialAccount::all();
        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function redirect(string $provider): JsonResponse
    {
        return response()->json([
            'success' => true,
            'auth_url' => "https://auth.example.com/{$provider}/oauth/authorize?client_id=demo",
        ]);
    }

    public function callback(string $provider): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => "Successfully authenticated {$provider} account.",
        ]);
    }

    public function disconnect(string $id): JsonResponse
    {
        $account = SocialAccount::find($id);
        if ($account) {
            $account->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Social account disconnected successfully.',
        ]);
    }
}
