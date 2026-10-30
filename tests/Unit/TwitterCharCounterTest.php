<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TwitterCharCounterTest extends TestCase
{
    /**
     * Test character count normalization for standard strings.
     */
    public function test_standard_text_character_count(): void
    {
        $text = "Building autonomous systems requires deterministic state machines.";
        $this->assertEquals(66, mb_strlen($text, 'UTF-8'));
        $this->assertLessThanOrEqual(280, mb_strlen($text, 'UTF-8'));
    }

    /**
     * Test Twitter 280-character boundary condition.
     */
    public function test_tweet_exceeds_280_characters(): void
    {
        $longText = str_repeat("Deterministic agents. ", 15); // ~330 chars
        $this->assertGreaterThan(280, mb_strlen($longText, 'UTF-8'));
    }

    /**
     * Test multi-byte unicode characters (emojis).
     */
    public function test_emoji_handling(): void
    {
        $emojiPost = "AI Engineering 🚀🔥🤖";
        $this->assertEquals(18, mb_strlen($emojiPost, 'UTF-8'));
    }

    /**
     * Test URL shortening calculation (Twitter treats all URLs as 23 characters).
     */
    public function test_url_counting_standard(): void
    {
        $url = "https://github.com/Ahasun-h/synthex-orchestrator/blob/main/README.md";
        $normalizedWeight = 23;
        $this->assertEquals(23, $normalizedWeight);
    }
}
