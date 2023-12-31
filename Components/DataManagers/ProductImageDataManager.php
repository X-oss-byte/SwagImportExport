<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers;

use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;

class ProductImageDataManager implements \Enlight_Hook
{
    private PDOConnection $db;

    /**
     * Define which field should be set by default
     *
     * @var array<string>
     */
    private array $defaultFields = [
        'main',
        'position',
        'thumbnail',
        'description',
    ];

    /**
     * initialises the class properties
     */
    public function __construct(PDOConnection $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function setDefaultFields(array $record, int $productId): array
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'main':
                    $record[$key] = 1;
                    break;
                case 'position':
                    $record[$key] = $this->getPosition($productId);
                    break;
                case 'thumbnail':
                    $record[$key] = true;
                    break;
                case 'description':
                    $record[$key] = '';
                    break;
            }
        }

        return $record;
    }

    private function getPosition(int $productId): int
    {
        $sql = 'SELECT MAX(position) FROM s_articles_img WHERE articleID = ?;';
        $result = $this->db->fetchOne($sql, $productId);

        return isset($result) ? ((int) $result + 1) : 1;
    }
}
