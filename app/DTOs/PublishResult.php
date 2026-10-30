<?php

namespace App\DTOs;

class PublishResult
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $externalPostId = null,
        public readonly ?string $externalPostUrl = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
        public readonly array $rawResponse = []
    ) {}

    public static function success(string $id, string $url, array $raw = []): self
    {
        return new self(
            isSuccess: true,
            externalPostId: $id,
            externalPostUrl: $url,
            rawResponse: $raw
        );
    }

    public static function failure(string $message, ?string $code = null, array $raw = []): self
    {
        return new self(
            isSuccess: false,
            errorMessage: $message,
            errorCode: $code,
            rawResponse: $raw
        );
    }
}
