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
use SwagImportExport\Setup\DefaultProfiles\ArticleSimilarsProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleSimilarsProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $articleSimilarsProfile = $this->createArticlSimilarsProfile();

        static::assertInstanceOf(ArticleSimilarsProfile::class, $articleSimilarsProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleSimilarsProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleSimilarsProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $articleSimilarsProfile = $this->createArticlSimilarsProfile();

        $this->walkRecursive($articleSimilarsProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createArticlSimilarsProfile(): ArticleSimilarsProfile
    {
        return new ArticleSimilarsProfile();
    }
}
