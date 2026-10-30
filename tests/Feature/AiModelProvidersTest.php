<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\AI\ClaudeTextGenerator;
use App\Services\AI\GeminiTextGenerator;
use App\Services\AI\OpenAiCompatibleTextGenerator;
use App\Services\AI\OpenAiTextGenerator;
use App\Services\AI\TextGeneratorInterface;
use App\Services\AI\TextGeneratorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiModelProvidersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_get_ai_config_returns_all_supported_providers(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/ai/config');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'active_provider',
                    'providers' => [
                        'gemini',
                        'openai',
                        'claude',
                        'z_ai',
                        'custom',
                    ],
                ],
            ]);
    }

    public function test_save_ai_config_persists_settings_securely(): void
    {
        $payload = [
            'active_provider' => 'z_ai',
            'providers' => [
                'z_ai' => [
                    'label' => 'Z AI',
                    'api_key' => 'z-ai-secret-key-99',
                    'base_url' => 'https://api.z-ai.io/v1',
                    'model' => 'z-ai-pro',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai/config', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Verify config retrieved reflects changes
        $activeConfig = TextGeneratorManager::getActiveConfig();
        $this->assertEquals('z_ai', $activeConfig['active_provider']);
        $this->assertEquals('z-ai-secret-key-99', $activeConfig['providers']['z_ai']['api_key']);
        $this->assertEquals('z-ai-pro', $activeConfig['providers']['z_ai']['model']);
    }

    public function test_driver_resolution_matches_active_configuration(): void
    {
        TextGeneratorManager::saveConfig([
            'active_provider' => 'claude',
            'providers' => [
                'claude' => [
                    'api_key' => 'anthropic-key-123',
                    'model' => 'claude-3-5-sonnet-20241022',
                ],
            ],
        ]);

        $driver = TextGeneratorManager::resolve();
        $this->assertInstanceOf(ClaudeTextGenerator::class, $driver);

        TextGeneratorManager::saveConfig([
            'active_provider' => 'openai',
            'providers' => [
                'openai' => [
                    'api_key' => 'sk-openai-key-456',
                    'model' => 'gpt-4o',
                ],
            ],
        ]);

        $driver = TextGeneratorManager::resolve();
        $this->assertInstanceOf(OpenAiTextGenerator::class, $driver);

        TextGeneratorManager::saveConfig([
            'active_provider' => 'custom',
            'providers' => [
                'custom' => [
                    'api_key' => 'ollama',
                    'base_url' => 'http://localhost:11434/v1',
                    'model' => 'llama3.2',
                ],
            ],
        ]);

        $driver = TextGeneratorManager::resolve();
        $this->assertInstanceOf(OpenAiCompatibleTextGenerator::class, $driver);
    }

    public function test_test_connection_endpoint_simulates_successful_probe(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'High-leverage engineering leadership requires clear vision.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ai/test', [
                'provider' => 'openai',
                'credentials' => [
                    'api_key' => 'sk-test-mock-key',
                    'model' => 'gpt-4o',
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'provider',
                'model',
                'latency_ms',
                'sample_output',
            ]);
    }
}
