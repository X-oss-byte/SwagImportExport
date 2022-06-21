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
use SwagImportExport\Setup\DefaultProfiles\NewsletterRecipientProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class NewsletterProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $newsletterProfile = new NewsletterRecipientProfile();

        static::assertInstanceOf(NewsletterRecipientProfile::class, $newsletterProfile);
        static::assertInstanceOf(ProfileMetaData::class, $newsletterProfile);
        static::assertInstanceOf(\JsonSerializable::class, $newsletterProfile);
    }

    public function testItShouldReturnValidProfile(): void
    {
        $newsletterProfile = new NewsletterRecipientProfile();

        $this->walkRecursive($newsletterProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });
    }
}
