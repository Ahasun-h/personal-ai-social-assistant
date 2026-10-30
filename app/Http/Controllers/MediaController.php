<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\MediaAsset;
use App\Models\Setting;

class MediaController extends Controller
{
    public function index(): Response
    {
        $assets = MediaAsset::query()
            ->latest()
            ->get();

        $budgetSetting = Setting::where('key', 'budget_guardrails')->first();

        return Inertia::render('Media/Index', [
            'assets' => $assets,
            'budget' => $budgetSetting ? $budgetSetting->value : [],
        ]);
    }
}
