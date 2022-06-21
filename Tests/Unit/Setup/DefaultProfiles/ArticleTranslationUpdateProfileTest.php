<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticleTranslationUpdateProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleTranslationUpdateProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $articleTranslationUpdateProfile = $this->createArticleTranslationUpdateProfile();

        static::assertInstanceOf(ArticleTranslationUpdateProfile::class, $articleTranslationUpdateProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleTranslationUpdateProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleTranslationUpdateProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $articleTranslationUpdateProfile = $this->createArticleTranslationUpdateProfile();

        $this->walkRecursive($articleTranslationUpdateProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createArticleTranslationUpdateProfile(): ArticleTranslationUpdateProfile
    {
        return new ArticleTranslationUpdateProfile();
    }
}
