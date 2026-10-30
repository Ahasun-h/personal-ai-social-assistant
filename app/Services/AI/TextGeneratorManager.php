<?php

namespace App\Services\AI;

use App\Contracts\AI\TextGeneratorInterface;
use App\DTOs\DraftPrompt;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TextGeneratorManager
{
    /**
     * Resolves the active or requested text generator driver.
     */
    public static function resolve(?string $overrideProvider = null): TextGeneratorInterface
    {
        $config = self::getActiveConfig();
        $provider = $overrideProvider ?? $config['active_provider'] ?? 'gemini';
        $providerSettings = $config['providers'][$provider] ?? [];

        return match ($provider) {
            'openai' => new OpenAiTextGenerator(
                apiKey: $providerSettings['api_key'] ?? null,
                model: $providerSettings['model'] ?? 'gpt-4o',
                baseUrl: $providerSettings['base_url'] ?? null
            ),
            'claude' => new ClaudeTextGenerator(
                apiKey: $providerSettings['api_key'] ?? null,
                model: $providerSettings['model'] ?? 'claude-3-5-sonnet-20241022'
            ),
            'z_ai' => new OpenAiCompatibleTextGenerator(
                apiKey: $providerSettings['api_key'] ?? null,
                baseUrl: $providerSettings['base_url'] ?? 'https://api.z-ai.io/v1',
                model: $providerSettings['model'] ?? 'z-ai-large',
                providerLabel: 'Z AI'
            ),
            'custom' => new OpenAiCompatibleTextGenerator(
                apiKey: $providerSettings['api_key'] ?? null,
                baseUrl: $providerSettings['base_url'] ?? 'http://localhost:11434/v1',
                model: $providerSettings['model'] ?? 'llama3.2',
                providerLabel: 'Custom OpenAI-Compatible'
            ),
            default => new GeminiTextGenerator(
                apiKey: $providerSettings['api_key'] ?? null,
                model: $providerSettings['model'] ?? 'gemini-1.5-pro'
            ),
        };
    }

    /**
     * Retrieves the stored AI provider configuration from the settings vault.
     */
    public static function getActiveConfig(): array
    {
        $user = auth()->user() ?: \App\Models\User::first();
        $query = Setting::where('key', 'ai_config');
        if ($user) {
            $query->where('user_id', $user->id);
        }
        $setting = $query->first() ?: Setting::where('key', 'ai_config')->first();

        $default = self::getDefaultConfig();

        if ($setting && is_array($setting->value)) {
            $saved = $setting->value;
            $merged = $default;
            $merged['active_provider'] = $saved['active_provider'] ?? $default['active_provider'];
            if (!empty($saved['providers']) && is_array($saved['providers'])) {
                foreach ($saved['providers'] as $k => $v) {
                    if (is_array($v)) {
                        $merged['providers'][$k] = array_merge($merged['providers'][$k] ?? [], $v);
                    }
                }
            }
            return $merged;
        }

        // Default initial config
        return $default;
    }

    /**
     * Fallback initial configuration populated from environment or defaults.
     */
    public static function getDefaultConfig(): array
    {
        return [
            'active_provider' => env('DEFAULT_AI_PROVIDER', 'gemini'),
            'providers' => [
                'gemini' => [
                    'label' => 'Google Gemini',
                    'api_key' => env('GEMINI_API_KEY', ''),
                    'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),
                    'is_configured' => !empty(env('GEMINI_API_KEY')),
                ],
                'openai' => [
                    'label' => 'OpenAI',
                    'api_key' => env('OPENAI_API_KEY', ''),
                    'model' => env('OPENAI_MODEL', 'gpt-4o'),
                    'is_configured' => !empty(env('OPENAI_API_KEY')),
                ],
                'claude' => [
                    'label' => 'Anthropic Claude',
                    'api_key' => env('ANTHROPIC_API_KEY', ''),
                    'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
                    'is_configured' => !empty(env('ANTHROPIC_API_KEY')),
                ],
                'z_ai' => [
                    'label' => 'Z AI',
                    'api_key' => env('Z_AI_API_KEY', ''),
                    'base_url' => env('Z_AI_BASE_URL', 'https://api.z-ai.io/v1'),
                    'model' => env('Z_AI_MODEL', 'z-ai-large'),
                    'is_configured' => !empty(env('Z_AI_API_KEY')),
                ],
                'custom' => [
                    'label' => 'Custom Endpoint (Groq / Ollama / DeepSeek)',
                    'api_key' => env('CUSTOM_AI_API_KEY', ''),
                    'base_url' => env('CUSTOM_AI_BASE_URL', 'http://localhost:11434/v1'),
                    'model' => env('CUSTOM_AI_MODEL', 'llama3.2'),
                    'is_configured' => !empty(env('CUSTOM_AI_BASE_URL')),
                ],
            ],
        ];
    }

    /**
     * Persists updated AI provider credentials securely.
     */
    public static function saveConfig(array $data): void
    {
        $user = auth()->user() ?: \App\Models\User::first();
        if (!$user) {
            return;
        }

        Setting::updateOrCreate(
            [
                'user_id' => $user->id,
                'key' => 'ai_config',
            ],
            [
                'value' => $data,
                'description' => 'User configured AI providers and models vault',
            ]
        );
    }

    /**
     * Tests connectivity to the designated AI provider.
     */
    public static function testConnection(string $provider, array $credentials): array
    {
        $startTime = microtime(true);
        $testTopic = "High-leverage engineering leadership";

        try {
            $driver = match ($provider) {
                'openai' => new OpenAiTextGenerator(
                    apiKey: $credentials['api_key'] ?? null,
                    model: $credentials['model'] ?? 'gpt-4o',
                    baseUrl: $credentials['base_url'] ?? null
                ),
                'claude' => new ClaudeTextGenerator(
                    apiKey: $credentials['api_key'] ?? null,
                    model: $credentials['model'] ?? 'claude-3-5-sonnet-20241022'
                ),
                'z_ai' => new OpenAiCompatibleTextGenerator(
                    apiKey: $credentials['api_key'] ?? null,
                    baseUrl: $credentials['base_url'] ?? 'https://api.z-ai.io/v1',
                    model: $credentials['model'] ?? 'z-ai-large',
                    providerLabel: 'Z AI'
                ),
                'custom' => new OpenAiCompatibleTextGenerator(
                    apiKey: $credentials['api_key'] ?? null,
                    baseUrl: $credentials['base_url'] ?? 'http://localhost:11434/v1',
                    model: $credentials['model'] ?? 'llama3.2',
                    providerLabel: 'Custom LLM'
                ),
                default => new GeminiTextGenerator(
                    apiKey: $credentials['api_key'] ?? null,
                    model: $credentials['model'] ?? 'gemini-1.5-pro'
                ),
            };

            $drafts = $driver->generateDrafts(new DraftPrompt($testTopic));
            $latencyMs = round((microtime(true) - $startTime) * 1000);

            return [
                'success' => true,
                'latency_ms' => $latencyMs,
                'provider' => $provider,
                'model' => $credentials['model'] ?? 'default',
                'sample_output' => mb_substr($drafts['linkedin'] ?? '', 0, 180) . '...',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }
}
