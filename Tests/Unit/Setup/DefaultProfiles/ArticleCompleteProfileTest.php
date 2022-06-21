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
use SwagImportExport\Setup\DefaultProfiles\ArticleCompleteProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleCompleteProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $articleProfile = new ArticleCompleteProfile();

        static::assertInstanceOf(ArticleCompleteProfile::class, $articleProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $articleAllProfile = $this->createArticleAllProfile();

        $this->walkRecursive($articleAllProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createArticleAllProfile(): ArticleCompleteProfile
    {
        return new ArticleCompleteProfile();
    }
}
