<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Shopware\Components\Migrations\AbstractPluginMigration;
use SwagImportExport\Models\Expression;
use SwagImportExport\Models\Logger;
use SwagImportExport\Models\Profile;
use SwagImportExport\Models\Session;

class Migration2 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        $schemaTool = new SchemaTool($this->getEntityManager());
        $doctrineModels = $this->getDoctrineModels();
        $schemaTool->updateSchema($doctrineModels, true);
    }

    public function down(bool $keepUserData): void
    {
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return Shopware()->Container()->get('models');
    }

    /**
     * @return array<ClassMetadata>
     */
    private function getDoctrineModels(): array
    {
        $manager = $this->getEntityManager();

        return [
            $manager->getClassMetadata(Session::class),
            $manager->getClassMetadata(Logger::class),
            $manager->getClassMetadata(Profile::class),
            $manager->getClassMetadata(Expression::class),
        ];
    }
}
