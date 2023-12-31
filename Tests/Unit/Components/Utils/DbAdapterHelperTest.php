<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\Utils;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Utils\DbAdapterHelper;

class DbAdapterHelperTest extends TestCase
{
    public function testDecodeHtmlEntities(): void
    {
        $inputRecords = [
            [
                'integer' => 100,
                'float' => 1.5,
                'textWithHtml' => '&lt;b&gt;With bold text with html entities&lt;/b&gt;',
                'false' => false,
                'true' => true,
                'string' => 'Hi, this is a string',
            ],
        ];

        $result = DbAdapterHelper::decodeHtmlEntities($inputRecords);

        static::assertSame('100', $result[0]['integer'], 'Could not decode integer');
        static::assertSame('1.5', $result[0]['float'], 'Could not decode float');
        static::assertSame('<b>With bold text with html entities</b>', $result[0]['textWithHtml'], 'Could not decode string with html tags');
        static::assertSame('0', $result[0]['false'], 'Could not decode boolean false');
        static::assertSame('1', $result[0]['true'], 'Could not decode boolean true');
        static::assertSame('Hi, this is a string', $result[0]['string'], 'Could not decode a string');
    }
}
