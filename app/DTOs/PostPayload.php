<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

class PostPayload
{
    public function __construct(
        public readonly string $draftId,
        public readonly string $content,
        public readonly string $platform,
        public readonly Collection $media, // Collection of MediaAsset
        public readonly array $threadItems = [],
        public readonly ?string $firstComment = null,
        public readonly array $metadata = []
    ) {}
}
