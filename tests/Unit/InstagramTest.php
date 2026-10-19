<?php

declare(strict_types=1);

namespace BaseTheme\Tests\Unit;

use BaseTheme\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class InstagramTest extends TestCase
{
    private static function media(array $overrides = []): array
    {
        return array_merge(
            [
                'id' => '17900000000000001',
                'media_type' => 'IMAGE',
                'media_url' => 'https://scontent.cdninstagram.com/v/a.jpg',
                'permalink' => 'https://www.instagram.com/p/ABC123/',
                'caption' => 'Frohe Weihnachten #henge #team',
                'timestamp' => '2025-12-24T10:00:00+0000',
            ],
            $overrides,
        );
    }

    public function test_alt_text_strips_hashtags_and_whitespace(): void
    {
        $this->assertSame(
            'Frohe Weihnachten vom Team!',
            hengegroup_theme_instagram_alt_text(
                "#xmas Frohe\n\nWeihnachten #henge vom Team! #team",
            ),
        );
        $this->assertSame('', hengegroup_theme_instagram_alt_text('#nur #hashtags'));
    }

    public function test_alt_text_is_shortened_on_word_boundary(): void
    {
        $this->assertSame(
            'Eins zwei drei…',
            hengegroup_theme_instagram_alt_text('Eins zwei drei, vier fuenf', 16),
        );
    }

    public function test_normalize_image_post(): void
    {
        $this->assertSame(
            [
                'id' => '17900000000000001',
                'image_url' => 'https://scontent.cdninstagram.com/v/a.jpg',
                'permalink' => 'https://www.instagram.com/p/ABC123/',
                'alt' => 'Frohe Weihnachten',
                'timestamp' => 1766570400,
            ],
            hengegroup_theme_normalize_instagram_media(self::media()),
        );
    }

    public function test_normalize_video_uses_thumbnail(): void
    {
        $post = hengegroup_theme_normalize_instagram_media(
            self::media([
                'media_type' => 'VIDEO',
                'media_url' => 'https://scontent.cdninstagram.com/v/clip.mp4',
                'thumbnail_url' => 'https://scontent.cdninstagram.com/v/thumb.jpg',
            ]),
        );

        $this->assertSame(
            'https://scontent.cdninstagram.com/v/thumb.jpg',
            $post['image_url'] ?? null,
        );
    }

    public static function invalidMediaProvider(): array
    {
        return [
            'non-numeric id' => [['id' => '../etc']],
            'unknown type' => [['media_type' => 'STORY']],
            'http image' => [['media_url' => 'http://example.com/a.jpg']],
            'foreign permalink' => [['permalink' => 'https://example.com/p/ABC/']],
            'video without thumbnail' => [['media_type' => 'VIDEO']],
        ];
    }

    #[DataProvider('invalidMediaProvider')]
    public function test_normalize_rejects_invalid_media(array $overrides): void
    {
        $this->assertNull(hengegroup_theme_normalize_instagram_media(self::media($overrides)));
    }
}
