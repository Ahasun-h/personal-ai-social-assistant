<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Draft;
use App\Models\SocialAccount;
use App\Models\InterestCategory;
use App\Models\User;
use App\Services\AI\TextGeneratorManager;

class InboxController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user() ?? User::first();

        $drafts = Draft::query()
            ->with(['versions.postMedia', 'postMedia.mediaAsset', 'category', 'trendItem'])
            ->whereIn('status', [Draft::STATUS_IN_REVIEW, Draft::STATUS_DRAFT])
            ->latest()
            ->get();

        $accounts = SocialAccount::where('is_active', true)->get();
        $categories = InterestCategory::where('is_active', true)->get();

        /** @var TrendController $trendController */
        $trendController = app(TrendController::class);
        $trendController->ensureCuratedTrendTopics();
        $preferences = $trendController->getPreferencesData();
        $trendingTopics = $trendController->getEnrichedTrends($preferences);

        return Inertia::render('Inbox/Index', [
            'drafts' => $drafts,
            'accounts' => $accounts,
            'categories' => $categories,
            'trendingTopics' => $trendingTopics,
            'preferences' => $preferences,
            'hasAiKey' => TextGeneratorManager::hasConfiguredApiKey(),
            'aiConfig' => TextGeneratorManager::getAiKeyStatus(),
        ]);
    }
}
