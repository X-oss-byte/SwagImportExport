<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DataManagers;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Functional\Components\DataManagers\Mocks\CustomerDataManagerMock;
use SwagImportExport\Tests\Helper\ContainerTrait;

class CustomerDataManagerTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    public function testSetDefaultFieldsForCreateShouldAddANewCustomerNumber(): void
    {
        $record = [];
        $defaultFields = [];

        $service = new CustomerDataManagerMock(
            $this->getContainer()->get('db'),
            $this->getContainer()->get('config'),
            $this->getContainer()->get('passwordencoder'),
            $this->getContainer()->get('shopware.number_range_incrementer')
        );

        $result = $service->setDefaultFieldsForCreate($record, $defaultFields);

        static::assertSame('bcrypt', $result['encoder']);
        static::assertStringStartsWith('20', $result['customernnumber']);
    }
}
