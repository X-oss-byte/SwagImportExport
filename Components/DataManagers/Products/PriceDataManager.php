<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers\Products;

use SwagImportExport\Components\DataManagers\DataManager;
use SwagImportExport\Components\DataType\ProductPriceDataType;

class PriceDataManager extends DataManager implements \Enlight_Hook
{
    public const NO_UPPER_LIMIT_GRADUATED_PRICES = 'beliebig';

    /**
     * Define which field should be set by default
     *
     * @var array<string>
     */
    private array $defaultFields = [
        'priceGroup',
        'from',
        'to',
    ];

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function setDefaultFields(array $record): array
    {
        foreach ($this->defaultFields as $key) {
            switch ($key) {
                case 'priceGroup':
                    $record[$key] = empty($record[$key]) ? 'EK' : $record[$key];
                    break;
                case 'from':
                    $record[$key] = empty($record[$key]) ? 1 : (int) $record[$key];
                    break;
                case 'to':
                    $record[$key] = $this->getTo($record[$key] ?? self::NO_UPPER_LIMIT_GRADUATED_PRICES);
                    break;
            }
        }

        return $record;
    }

    public function getDefaultFields(): array
    {
        return [];
    }

    /**
     * Return proper values for article price fields which have values NULL
     *
     * @param array<string, mixed> $records
     *
     * @return array<string, mixed>
     */
    public function fixDefaultValues(array $records): array
    {
        $defaultFieldsValues = ProductPriceDataType::$defaultFieldsValues;

        return $this->fixFieldsValues($records, $defaultFieldsValues);
    }

    /**
     * @param string|int $to
     *
     * @return int|string
     */
    private function getTo($to)
    {
        $to = !empty($to) ? (int) $to : 0;

        // if the "to" value isn't numeric, set the placeholder 'beliebig'
        if ($to <= 0) {
            $to = self::NO_UPPER_LIMIT_GRADUATED_PRICES;
        }

        return $to;
    }
}
