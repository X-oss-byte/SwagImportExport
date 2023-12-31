<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ProductSimilarsProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testWriteShouldAssertNewSimilarProduct(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_similars_profile.csv';
        $expectedOrderNumber = 'SW10003';
        $expectedRelatedProductId = [
            0 => 2,
            1 => 4,
            2 => 6,
        ];

        $this->runCommand("sw:import:import -p default_similar_articles {$filePath}");

        $updatedProductId = $this->executeQuery(sprintf("SELECT articleID FROM s_articles_details WHERE ordernumber='%s'", $expectedOrderNumber))[0]['articleID'];
        $updatedProductSimilars = $this->executeQuery(sprintf("SELECT * FROM s_articles_similar WHERE articleID='%s'", $updatedProductId));

        foreach (\array_keys($expectedRelatedProductId) as $key) {
            static::assertEquals($expectedRelatedProductId[$key], $updatedProductSimilars[$key]['relatedarticle']);
        }

        // Now deleted element
        static::assertEmpty($updatedProductSimilars[3]);
    }
}
