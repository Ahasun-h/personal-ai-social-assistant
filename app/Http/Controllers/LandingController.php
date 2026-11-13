<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    /**
     * Display the public showcase / landing page.
     */
    public function index(): Response|RedirectResponse
    {
        return Inertia::render('Landing', [
            'meta' => [
                'name' => 'Synthex AI Social Command',
                'version' => '2.4.0',
                'release_tag' => 'v2.4-enterprise',
                'tagline' => 'Enterprise Autonomous AI Social Media Command Center for LinkedIn & X',
                'metrics' => [
                    'tests_passed' => 47,
                    'assertions' => 315,
                    'daily_cap_enforced' => true,
                    'zero_unapproved_publish' => true,
                    'active_queues' => 4,
                ],
                'integrations' => [
                    'social' => ['LinkedIn (UGC / Shares API)', 'X (Twitter v2 API with PKCE)'],
                    'llm' => ['Google Gemini 1.5 / 2.5', 'Anthropic Claude 3.5 Sonnet', 'OpenAI GPT-4o'],
                    'media' => ['Flux.1 Schnell (Replicate)', 'DALL-E 3', 'Seedance AI Video'],
                    'queues' => ['publish-critical', 'ai-generation', 'media-transcode', 'trend-sync'],
                ],
            ],
        ]);
    }
}
