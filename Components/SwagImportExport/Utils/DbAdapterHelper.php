<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Utils;

class DbAdapterHelper
{
    /**
     * @param array<array<string, mixed>> $records
     *
     * @return array<array<string, string>>
     */
    public static function decodeHtmlEntities($records)
    {
        foreach ($records as &$record) {
            foreach ($record as &$value) {
                if (\is_bool($value)) {
                    $value = self::convertBooleanToString($value);
                }
                if ($value instanceof \DateTime) {
                    $value = $value->format(\DateTimeInterface::ATOM);
                }

                if (!\is_array($value)) {
                    $value = \html_entity_decode($value, \ENT_COMPAT | \ENT_HTML401, 'UTF-8');
                }
            }
        }

        return $records;
    }

    /**
     * @param array<array<string, mixed>> $records
     *
     * @return array<array<string, string>>
     */
    public static function escapeNewLines($records)
    {
        foreach ($records as &$record) {
            foreach ($record as &$value) {
                $value = \str_replace(["\n", "\r", "\r\n", "\n\r"], ' ', $value);
            }
        }

        return $records;
    }

    /**
     * html_entity_encode would return an empty string if boolean false is passed.
     */
    private static function convertBooleanToString(bool $value): string
    {
        return $value ? '1' : '0';
    }
}
