<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Migrations;

use Shopware\Components\Migrations\AbstractMigration;
use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration3 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        if ($modus === AbstractMigration::MODUS_UPDATE) {
            return;
        }

        $pluginId = (int) $this->connection->query("SELECT id FROM s_core_plugins WHERE name = 'SwagImportExport'")->fetchColumn();

        $aclResourceId = (int) $this->connection->query("SELECT id FROM s_core_acl_resources WHERE name = 'swagimportexport'")->fetchColumn();

        if ($aclResourceId === 0) {
            $sql = <<<SQL
INSERT INTO s_core_acl_resources
(`name`, `pluginID`)
VALUES
('swagimportexport', :pluginId);
SQL;
            $this->connection->prepare($sql)->execute(['pluginId' => $pluginId]);
            $aclResourceId = $this->connection->lastInsertId();
        } else {
            $sql = <<<SQL
UPDATE s_core_acl_resources
SET `pluginID` = :pluginId
WHERE id = :aclResourceId;
SQL;
            $this->connection->prepare($sql)->execute(['pluginId' => $pluginId, 'aclResourceId' => $aclResourceId]);
        }

        foreach (['export', 'import', 'profile', 'read'] as $action) {
            $sql = <<<SQL
            INSERT IGNORE INTO s_core_acl_privileges
            (`name`, `resourceID`)
            VALUES
            (:action, :aclResourceId);
            SQL;

            $this->connection->prepare($sql)->execute([
                'action' => $action,
                'aclResourceId' => $aclResourceId,
            ]);
        }
    }

    public function down(bool $keepUserData): void
    {
        if ($keepUserData) {
            return;
        }

        $resourceId = $this->connection->query("SELECT id from s_core_acl_resources WHERE name = 'swagimportexport'")->fetchColumn();

        if (!\is_string($resourceId)) {
            throw new \RuntimeException('No result');
        }

        $this->connection->prepare(<<<SQL
            DELETE FROM s_core_acl_privileges WHERE resourceID = :resourceId
SQL)->execute(['resourceId' => $resourceId]);

        $this->connection->exec(<<<SQL
            DELETE FROM s_core_acl_resources WHERE name = 'swagimportexport'
SQL);
    }
}
