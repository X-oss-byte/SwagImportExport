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

class ProductImageUrlProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testImportShouldAddNewImageToProduct(): void
    {
        $imagePath = 'file://' . \realpath(__DIR__) . '/../../../Helper/ImportFiles/sw-icon_blue128.png';
        $importFile = $this->getImportFile('article_image_url_create.csv');

        \file_put_contents($importFile, 'ordernumber;mainnumber;imageUrl');

        // writes importdata with actual imagePath to csv to use internal file for import test
        \file_put_contents(
            $importFile,
            "\r\n" . \implode(';', ['SW10001', 'SW10001', $imagePath]),
            \FILE_APPEND
        );

        $this->runCommand("sw:import:import -p default_article_images_url {$importFile}");

        $productResult = $this->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10001'");
        $images = $this->executeQuery(sprintf("SELECT * FROM s_articles_img WHERE articleID = '%s'", $productResult[0]['articleID']));

        static::assertEquals($productResult[0]['articleID'], $images[1]['articleID']);
        static::assertEquals(2, $images[1]['position']);
        static::assertStringStartsWith('sw-icon_blue', $images[1]['img']);

        // removes generated import line and resets csv to initial state
        \file_put_contents(
            $importFile,
            \implode(';', ['ordernumber', 'mainnumber', 'imageUrl'])
        );
    }
}
