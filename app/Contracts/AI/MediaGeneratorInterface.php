<?php

namespace App\Contracts\AI;

interface MediaGeneratorInterface
{
    /**
     * Dispatches image generation request and returns asset metadata or temporary URL.
     */
    public function generateImage(string $prompt, string $aspectRatio = '1:1', array $options = []): array;

    /**
     * Polls status of async generation request if applicable.
     */
    public function checkStatus(string $requestId): array;

    /**
     * Estimates cost in USD for the requested generation.
     */
    public function estimateCost(string $model, string $aspectRatio): float;
}
