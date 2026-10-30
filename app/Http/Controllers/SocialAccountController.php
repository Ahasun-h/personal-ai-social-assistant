<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = SocialAccount::where('is_active', true)->get()->map(function ($acc) {
            return [
                'id' => $acc->id,
                'provider' => $acc->provider,
                'name' => $acc->name,
                'username' => $acc->username,
                'avatar_url' => $acc->avatar_url,
                'token_expires_at' => $acc->token_expires_at?->toISOString(),
                'days_until_expiry' => $acc->daysUntilExpiry(),
                'is_expired' => $acc->isExpired(),
                'is_active' => $acc->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function redirect(string $provider): JsonResponse
    {
        if (!in_array($provider, ['linkedin', 'x_twitter'])) {
            return response()->json([
                'success' => false,
                'error' => ['title' => 'Invalid Provider', 'status' => 400],
            ], 400);
        }

        $url = $provider === 'linkedin'
            ? "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=" . env('LINKEDIN_CLIENT_ID') . "&redirect_uri=" . urlencode(env('LINKEDIN_REDIRECT_URI')) . "&scope=openid%20profile%20email%20w_member_social"
            : "https://twitter.com/i/oauth2/authorize?response_type=code&client_id=" . env('TWITTER_CLIENT_ID') . "&redirect_uri=" . urlencode(env('APP_URL') . '/api/v1/accounts/oauth/x_twitter/callback') . "&scope=tweet.read%20tweet.write%20users.read%20offline.access&code_challenge=challenge&code_challenge_method=plain";

        return response()->json([
            'success' => true,
            'data' => ['authorization_url' => $url],
        ]);
    }

    public function callback(Request $request, string $provider): JsonResponse
    {
        $code = $request->input('code', 'mock_code');
        $user = User::first() ?? User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password_hash' => bcrypt('password123'),
        ]);

        $account = SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_account_id' => $provider === 'linkedin' ? 'urn:li:person:demo_owner' : 'tw_123456789',
            ],
            [
                'name' => $provider === 'linkedin' ? 'Personal Profile' : 'Personal X',
                'username' => $provider === 'linkedin' ? 'personal-linkedin' : 'personal_x',
                'access_token' => 'mock_access_token_' . $code,
                'refresh_token' => 'mock_refresh_token',
                'token_expires_at' => now()->addDays(60),
                'is_active' => true,
            ]
        );

        AuditLog::record('connect_account', $account, $user->id, null, ['provider' => $provider]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $account->id,
                'provider' => $account->provider,
                'name' => $account->name,
                'username' => $account->username,
                'token_expires_at' => $account->token_expires_at->toISOString(),
                'days_remaining' => 60,
            ],
        ], 201);
    }

    public function disconnect(string $id): JsonResponse
    {
        $account = SocialAccount::findOrFail($id);
        $account->update(['is_active' => false]);

        AuditLog::record('disconnect_account', $account);

        return response()->json([
            'success' => true,
            'data' => ['disconnected' => true],
        ]);
    }
}
