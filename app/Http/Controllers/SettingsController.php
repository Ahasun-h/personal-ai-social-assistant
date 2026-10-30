<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\SocialAccount;
use App\Models\Setting;
use App\Models\InterestCategory;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $accounts = SocialAccount::all();
        $categories = InterestCategory::all();
        $settings = Setting::all()->keyBy('key');

        return Inertia::render('Settings/Index', [
            'accounts' => $accounts,
            'categories' => $categories,
            'brandVoice' => $settings->get('brand_voice')?->value ?? [],
            'budget' => $settings->get('budget_guardrails')?->value ?? [],
        ]);
    }
}

