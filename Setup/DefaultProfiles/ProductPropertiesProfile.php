<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class ProductPropertiesProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::PRODUCT_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_article_properties';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_article_properties_description';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' => [
                0 => [
                    'id' => '4',
                    'name' => 'articles',
                    'index' => 0,
                    'type' => '',
                    'children' => [
                        0 => [
                            'id' => '53e0d3148b0b2',
                            'name' => 'article',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'article',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getProductPropertiesFields(),
                            'attributes' => null,
                        ],
                    ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }

    private function getProductPropertiesFields(): array
    {
        return [
            0 => [
                'id' => '53e0d365881b7',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'ordernumber',
                'shopwareField' => 'orderNumber',
            ],
            1 => [
                'id' => '53e0d329364c4',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'mainnumber',
                'shopwareField' => 'mainNumber',
            ],
            2 => [
                'id' => '55dc553cb00e5',
                'name' => 'propertyValue',
                'index' => 2,
                'type' => 'iteration',
                'adapter' => 'propertyValue',
                'parentKey' => 'articleId',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '55dc554a6db5f',
                        'type' => 'leaf',
                        'index' => 0,
                        'name' => 'propertyGroupName',
                        'shopwareField' => 'propertyGroupName',
                    ],
                    1 => [
                        'id' => '55dc5550e7e30',
                        'type' => 'leaf',
                        'index' => 1,
                        'name' => 'propertyValueName',
                        'shopwareField' => 'propertyValueName',
                    ],
                    2 => [
                        'id' => '55dc555869ed8',
                        'type' => 'leaf',
                        'index' => 2,
                        'name' => 'propertyOptionName',
                        'shopwareField' => 'propertyOptionName',
                    ],
                ],
            ],
        ];
    }
}
