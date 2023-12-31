<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators\Products;

use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\Validator;

class PriceValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'float' => [
            'price',
            'purchasePrice',
            'pseudoPrice',
            'regulationPrice',
        ],
    ];

    /**
     * @var array<array<string>>
     */
    protected array $requiredFields = [
        ['price', 'priceGroup'],
    ];

    /**
     * @var array<string, array<string>>
     */
    protected array $snippetData = [
        'price' => [
            'adapters/articles/incorrect_price',
            'Price value is incorrect for article with number %s',
        ],
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @param array<string, mixed> $record
     */
    public function checkRequiredFields(array $record, string $orderNumber = ''): void
    {
        foreach ($this->requiredFields as $key) {
            [$price, $priceGroup] = $key;
            if (!empty($record[$price]) || $record[$priceGroup] !== 'EK') {
                continue;
            }

            $key = $price;

            [$snippetName, $snippetMessage] = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $orderNumber));
        }
    }
}
