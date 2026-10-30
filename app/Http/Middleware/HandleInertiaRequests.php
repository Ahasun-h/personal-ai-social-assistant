<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\User;
use App\Models\SocialAccount;
use App\Models\Setting;
use App\Models\Draft;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $currentUser = $request->user() ?: User::first();
        $accounts = SocialAccount::where('is_active', true)->get()->map(function ($acc) {
            $daysLeft = $acc->token_expires_at ? max(0, (int) now()->diffInDays($acc->token_expires_at, false)) : null;
            return [
                'id' => $acc->id,
                'provider' => $acc->provider,
                'name' => $acc->name,
                'username' => $acc->username,
                'avatar_url' => $acc->avatar_url,
                'days_remaining' => $daysLeft,
                'is_active' => $acc->is_active,
            ];
        });

        $budgetSetting = Setting::where('key', 'budget_guardrails')->first();
        $budget = $budgetSetting ? $budgetSetting->value : [
            'monthly_llm_cap_usd' => 150.00,
            'current_month_llm_spend_usd' => 18.42,
        ];

        $inboxCount = Draft::where('status', Draft::STATUS_IN_REVIEW)->count();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $currentUser ? [
                    'id' => $currentUser->id,
                    'name' => $currentUser->name,
                    'email' => $currentUser->email,
                    'avatar_url' => $currentUser->avatar_url,
                    'role' => $currentUser->role,
                ] : null,
            ],
            'connectedAccounts' => $accounts,
            'budget' => $budget,
            'inboxCount' => $inboxCount,
            'activeAi' => [
                'provider' => \App\Services\AI\TextGeneratorManager::getActiveConfig()['active_provider'] ?? 'gemini',
                'model' => \App\Services\AI\TextGeneratorManager::getActiveConfig()['providers'][\App\Services\AI\TextGeneratorManager::getActiveConfig()['active_provider'] ?? 'gemini']['model'] ?? 'gemini-1.5-pro',
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
