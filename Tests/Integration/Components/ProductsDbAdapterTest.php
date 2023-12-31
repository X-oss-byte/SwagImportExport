<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use Doctrine\DBAL\Query\QueryBuilder;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsDbAdapter;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\DbAdapterTestHelper;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class ProductsDbAdapterTest extends DbAdapterTestHelper
{
    use FixturesImportTrait;

    private const PRODUCT_VARIANTS_IDS = [123, 124, 14, 257, 15, 258, 16, 259, 253, 254, 255, 250, 251];

    protected string $yamlFile = 'TestCases/articleDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = DataDbAdapter::PRODUCT_ADAPTER;
        $this->dbTable = ProfileDataProvider::PRODUCT_TABLE;
    }

    /**
     * @param int[] $ids
     *
     * @dataProvider readProvider
     */
    public function testRead(array $columns, array $ids, array $expected): void
    {
        $rawData = $this->createProductsDbAdapter()->read($ids, $columns);

        foreach ($rawData['article'] as &$item) {
            unset($item['articleId']);
        }
        unset($item);

        static::assertContains($rawData['article'][0], $expected['article']);
        static::assertContains($rawData['article'][1], $expected['article']);
        static::assertCount(\count($rawData['article']), $expected['article']);
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite(array $data): void
    {
        $expectedOrderNumber = 'test9999';

        $dbAdapter = $this->createProductsDbAdapter();
        $dbAdapter->write($data);

        $product = $this->getProductDataResult($expectedOrderNumber);
        $prices = $this->getProductPriceResult($expectedOrderNumber);

        static::assertEquals('test9999', $product[0]['ordernumber']);
        static::assertEquals('shopware-test1', $product[0]['name']);

        static::assertEquals('EK', $prices[0]['pricegroup']);
        static::assertEquals(84.033613445378, $prices[0]['price']);

        static::assertEquals('H', $prices[1]['pricegroup']);
        static::assertEquals(50, $prices[1]['price']);
    }

    public function testReadVariantIdsOfProductStream(): void
    {
        $this->addProductStream();

        $filter = [
            ProductsDbAdapter::VARIANTS_FILTER_KEY => true,
            ProductsDbAdapter::PRODUCT_STREAM_ID_FILTER_KEY => [
                0 => 999999,
            ],
        ];

        $recordIds = $this->createProductsDbAdapter()->readRecordIds(0, \PHP_INT_MAX, $filter);

        static::assertCount(13, array_intersect(self::PRODUCT_VARIANTS_IDS, $recordIds));
    }

    public function readProvider(): array
    {
        return $this->getDataProvider('testRead');
    }

    public function writeProvider(): array
    {
        return $this->getDataProvider('testWrite');
    }

    private function createProductsDbAdapter(): DataDbAdapter
    {
        return $this->getContainer()->get(DataProvider::class)->createDbAdapter($this->dbAdapter);
    }

    private function getProductDataResult(string $number): array
    {
        return $this->getQueryBuilder()
            ->select(['details.ordernumber', 'articles.name', 'prices.price'])
            ->from('s_articles', 'articles')
            ->leftJoin('articles', 's_articles_details', 'details', 'details.articleID = articles.id')
            ->leftJoin('details', 's_articles_prices', 'prices', 'prices.articledetailsID = details.id')
            ->where('details.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()
            ->fetchAllAssociative();
    }

    private function getProductPriceResult(string $number): array
    {
        return $this->getQueryBuilder()
            ->select('prices.pricegroup', 'prices.price')
            ->from('s_articles_prices', 'prices')
            ->leftJoin('prices', 's_articles_details', 'details', 'details.id = prices.articledetailsID')
            ->where('details.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()
            ->fetchAllAssociative();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->getContainer()->get('dbal_connection')->createQueryBuilder();
    }
}
