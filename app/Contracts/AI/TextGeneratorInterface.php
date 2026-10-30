<?php

namespace App\Contracts\AI;

use App\DTOs\DraftPrompt;

interface TextGeneratorInterface
{
    /**
     * Generates a pair of platform-specific drafts for LinkedIn and X.
     * Returns array with keys 'linkedin' and 'x_twitter'.
     */
    public function generateDrafts(DraftPrompt $prompt): array;

    /**
     * Refines existing content according to user instructions (shorten, expand, tone, hooks).
     */
    public function refine(string $content, string $instruction, string $platform): string;

    /**
     * Generates 5 alternative opening hooks for a post.
     */
    public function generateHooks(string $topic, string $platform): array;

    /**
     * Moderates text content against safety guidelines.
     */
    public function moderate(string $text): bool;
}
