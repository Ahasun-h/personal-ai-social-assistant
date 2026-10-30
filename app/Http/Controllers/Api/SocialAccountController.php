<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SocialAccount;
use App\Models\User;

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:linkedin,x_twitter',
            'name' => 'required|string',
            'username' => 'required|string',
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'provider_account_id' => 'nullable|string',
        ]);

        $user = User::first() ?? User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password_hash' => bcrypt('password123'),
        ]);

        $account = SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $validated['provider'],
            ],
            [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'access_token' => $validated['access_token'],
                'refresh_token' => $validated['refresh_token'] ?? null,
                'provider_account_id' => $validated['provider_account_id'] ?? ($validated['provider'] . '_' . time()),
                'token_expires_at' => now()->addDays(60),
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Connected {$validated['provider']} account successfully.",
            'data' => $account,
        ], 200);
    }

    public function redirect(string $provider): JsonResponse
    {
        $clientId = $provider === 'linkedin'
            ? env('LINKEDIN_CLIENT_ID', 'mock_li_client_id')
            : env('X_CLIENT_ID', 'mock_x_client_id');

        $redirectUri = urlencode(env('APP_URL', 'http://localhost:8000') . "/api/v1/accounts/oauth/{$provider}/callback");

        $url = $provider === 'linkedin'
            ? "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&scope=openid%20profile%20email%20w_member_social"
            : "https://twitter.com/i/oauth2/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&scope=tweet.read%20tweet.write%20users.read%20offline.access";

        return response()->json([
            'success' => true,
            'auth_url' => $url,
        ]);
    }

    public function callback(Request $request, string $provider): JsonResponse
    {
        $code = $request->input('code', 'auth_code_' . time());
        $user = User::first();

        $account = SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'name' => $provider === 'linkedin' ? 'LinkedIn Member' : 'X User',
                'username' => $provider === 'linkedin' ? 'linkedin_profile' : 'x_handle',
                'access_token' => 'oauth_bearer_' . $code,
                'refresh_token' => 'oauth_refresh_' . time(),
                'provider_account_id' => $provider . '_' . time(),
                'token_expires_at' => now()->addDays(60),
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully connected {$provider} account.",
            'data' => $account,
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
