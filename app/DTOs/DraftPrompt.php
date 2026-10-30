<?php

namespace App\DTOs;

class DraftPrompt
{
    public function __construct(
        public readonly string $topic,
        public readonly ?string $context = null,
        public readonly string $toneStyle = 'thought_leader',
        public readonly array $targetPlatforms = ['linkedin', 'x_twitter'],
        public readonly array $keywords = [],
        public readonly array $bannedWords = ['synergy', 'delve', 'game-changer', 'testament to']
    ) {}
}
