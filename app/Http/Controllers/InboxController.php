<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Draft;
use App\Models\SocialAccount;
use App\Models\InterestCategory;

class InboxController extends Controller
{
    public function index(): Response
    {
        $drafts = Draft::query()
            ->with(['versions.postMedia', 'postMedia.mediaAsset', 'category', 'trendItem'])
            ->whereIn('status', [Draft::STATUS_IN_REVIEW, Draft::STATUS_DRAFT])
            ->latest()
            ->get();

        $accounts = SocialAccount::where('is_active', true)->get();
        $categories = InterestCategory::where('is_active', true)->get();

        return Inertia::render('Inbox/Index', [
            'drafts' => $drafts,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }
}

