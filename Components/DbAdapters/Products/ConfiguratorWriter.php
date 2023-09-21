<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;
use SwagImportExport\Components\DbAdapters\Results\ProductWriterResult;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\Products\ConfiguratorValidator;

class ConfiguratorWriter
{
    private ConfiguratorValidator $configuratorValidator;

    private DbalHelper $dbalHelper;

    private Connection $connection;

    private \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    /**
     * @var array<int|string, mixed>
     */
    private array $sets = [];

    public function __construct(
        DbalHelper $dbalHelper,
        Connection $connection,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db
    ) {
        $this->dbalHelper = $dbalHelper;
        $this->connection = $connection;
        $this->db = $db;
        $this->configuratorValidator = new ConfiguratorValidator();
    }

    /**
     * @param array<int, mixed> $configuratorData
     *
     * @throws AdapterException
     */
    public function writeOrUpdateConfiguratorSet(ProductWriterResult $productWriterResult, array $configuratorData): void
    {
        if (empty($this->sets)) {
            $this->sets = $this->getSets();
        }

        foreach ($configuratorData as $configurator) {
            $configuratorSetId = null;
            $optionId = null;

            if (!$this->isValid($configurator)) {
                continue;
            }

            $configurator = $this->configuratorValidator->filterEmptyString($configurator);
            $this->configuratorValidator->validate($configurator, ConfiguratorValidator::$mapper);

            /**
             * Updates the type of a configurator set
             */
            $configuratorSetId = $this->updateConfiguratorSetTypeIfConfigSetIdIsNotEmptyAndSetDoesExistAndMatchSetName($productWriterResult->getProductId(), $configuratorSetId, $configurator);

            if (!$configuratorSetId) {
                $configuratorSetId = $this->getConfiguratorSetIdByProductId($productWriterResult->getProductId());
            }

            if (!$configuratorSetId) {
                if (empty($configurator['configSetName'])) {
                    $orderNumber = $this->getOrderNumber($productWriterResult->getProductId());
                    $dataSet['name'] = 'Set-' . $orderNumber;
                } else {
                    $dataSet['name'] = $configurator['configSetName'];
                }

                $dataSet['public'] = false;
                $dataSet['id'] = $configurator['configSetId'] ?? null;
                if ($configurator['configSetType']) {
                    $dataSet['type'] = $configurator['configSetType'];
                }

                if (\array_key_exists($dataSet['name'], $this->sets)) {
                    $configuratorSetId = (int) $this->sets[$dataSet['name']];
                } else {
                    $configuratorSetId = $this->createSet($dataSet);
                    $this->sets[$dataSet['name']] = $configuratorSetId;
                }
            }

            if ($productWriterResult->getMainDetailId() != $productWriterResult->getDetailId()) {
                $this->updateProductSetsRelation($productWriterResult->getProductId(), $configuratorSetId);
            }

            /*
             * configurator option
             */
            if (!empty($configurator['configOptionId'])) {
                $optionResult = $this->getOptionRow($configurator['configOptionId']);

                $optionId = (int) $optionResult['id'];
                $groupId = (int) $optionResult['group_id'];

                if (!$optionId) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/config_option_not_found', 'ConfiguratorOption with id %s not found');
                    throw new AdapterException(\sprintf($message, $configurator['configOptionId']));
                }
            } else {
                // gets or creates configurator group
                $groupId = $this->getConfiguratorGroup($configurator);
            }

            $this->updateGroupsRelation($configuratorSetId, $groupId);

            if (isset($configurator['configOptionName']) && !$optionId) {
                $optionId = $this->getOptionIdByOptionNameAndGroupId($configurator['configOptionName'], $groupId);
            }

            // creates option
            if (!$optionId) {
                if (!empty($configurator['configOptionPosition'])) {
                    $position = $configurator['configOptionPosition'];
                } else {
                    $position = $this->getNextOptionPosition($groupId);
                }

                $dataOption = [
                    'groupId' => $groupId,
                    'name' => $configurator['configOptionName'],
                    'position' => $position,
                ];

                $optionId = $this->createOption($dataOption);
            }

            $this->updateOptionRelation($productWriterResult->getDetailId(), $optionId);
            $this->updateSetOptionRelation($configuratorSetId, $optionId);

            unset($groupId, $optionId, $configuratorSetId);
        }
    }

    private function getSetIdBySetName(string $name): ?int
    {
        return $this->sets[$name] ? (int) $this->sets[$name] : null;
    }

    private function getGroupIdByGroupName(string $name): ?int
    {
        $sql = 'SELECT `id`
                FROM s_article_configurator_groups
                WHERE `name` = ?';

        $id = $this->connection->fetchOne($sql, [$name]);

        return $id ? (int) $id : null;
    }

    private function getOptionIdByOptionNameAndGroupId(string $optionName, int $groupId): ?int
    {
        $sql = 'SELECT `id`
                FROM s_article_configurator_options
                WHERE `name` = ? AND `group_id` = ?';

        $id = $this->db->fetchOne($sql, [$optionName, $groupId]);

        return $id ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getOptionRow(int $id): array
    {
        $sql = 'SELECT `id`, `group_id`, `name`, `position`
                FROM s_article_configurator_options
                WHERE `id` = ?';

        return $this->db->fetchRow($sql, [$id]);
    }

    private function updateProductSetsRelation(int $productId, int $setId): void
    {
        $this->db->query('UPDATE s_articles SET configurator_set_id = ? WHERE id = ?', [$setId, $productId]);
    }

    private function updateGroupsRelation(int $setId, int $groupId): void
    {
        $sql = "INSERT INTO s_article_configurator_set_group_relations (set_id, group_id)
                VALUES ($setId, $groupId)
                ON DUPLICATE KEY UPDATE set_id=VALUES(set_id), group_id=VALUES(group_id)";

        $this->connection->executeStatement($sql);
    }

    private function updateOptionRelation(int $productDetailId, int $optionId): void
    {
        $sql = "INSERT INTO s_article_configurator_option_relations (article_id, option_id)
                VALUES ($productDetailId, $optionId)
                ON DUPLICATE KEY UPDATE article_id=VALUES(article_id), option_id=VALUES(option_id)";

        $this->connection->executeStatement($sql);
    }

    private function updateSetOptionRelation(int $setId, int $optionId): void
    {
        $sql = "INSERT INTO s_article_configurator_set_option_relations (set_id, option_id)
                VALUES ($setId, $optionId)
                ON DUPLICATE KEY UPDATE set_id=VALUES(set_id), option_id=VALUES(option_id)";

        $this->connection->executeStatement($sql);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getSets(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT `name`, `id` FROM s_article_configurator_sets');
    }

    private function getConfiguratorSetIdByProductId(int $productId): ?int
    {
        $id = $this->connection->fetchOne('SELECT configurator_set_id FROM s_articles WHERE id = ?', [$productId]);

        return $id ? (int) $id : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSet(array $data): int
    {
        // Delete id to avoid unique constraint violations
        unset($data['id']);

        $builder = $this->dbalHelper->getQueryBuilderForEntity($data, Set::class, null);
        $builder->execute();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createGroup(array $data): int
    {
        $builder = $this->dbalHelper->getQueryBuilderForEntity($data, Group::class, null);
        $builder->execute();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createOption(array $data): int
    {
        $builder = $this->dbalHelper->getQueryBuilderForEntity($data, Option::class, null);
        $builder->execute();

        return (int) $this->connection->lastInsertId();
    }

    private function getOrderNumber(int $productId): ?string
    {
        $sql = 'SELECT `ordernumber`
                FROM s_articles_details
                WHERE kind = 1 AND articleID = ?';

        return $this->connection->fetchOne($sql, [$productId]) ?: null;
    }

    /**
     * This function updates a specific database record for a configurator set.
     *
     * @param array<string, mixed> $configurator
     */
    private function updateConfiguratorSet(array $configurator): void
    {
        $sql = 'UPDATE s_article_configurator_sets SET
                type=:setType
                WHERE id=:id';

        $this->db->executeQuery($sql, ['setType' => $configurator['configSetType'], 'id' => $configurator['configSetId']]);
    }

    /**
     * @param array<string, mixed> $configurator
     */
    private function isValid(array $configurator): bool
    {
        if (empty($configurator['configOptionId'])) {
            if (!isset($configurator['configGroupName']) && !isset($configurator['configGroupId'])) {
                return false;
            }

            if (empty($configurator['configGroupName']) && empty($configurator['configGroupId'])) {
                return false;
            }

            if (!isset($configurator['configOptionName'])) {
                return false;
            }
        }

        return true;
    }

    private function checkExistence(string $table, int $id): bool
    {
        $sql = sprintf('SELECT `id` FROM %s WHERE id = ?', $table);
        $result = $this->connection->fetchOne($sql, [$id]);

        return (bool) $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getConfiguratorGroup(array $data): int
    {
        $groupId = null;

        if (isset($data['configGroupId'])
            && $this->checkExistence('s_article_configurator_groups', (int) $data['configGroupId'])
        ) {
            $groupId = $data['configGroupId'];
        }

        if (isset($data['configGroupName']) && !$groupId) {
            $groupId = $this->getGroupIdByGroupName($data['configGroupName']);

            if (!$groupId) {
                $groupPosition = $this->getNextGroupPosition();
                $groupData = [
                    'name' => $data['configGroupName'],
                    'position' => $groupPosition,
                ];

                $groupId = $this->createGroup($groupData);
            }
        }

        if (!$groupId) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/provide_groupname_groupid', 'Please provide groupname or groupId');
            throw new AdapterException($message);
        }

        return (int) $groupId;
    }

    private function getNextGroupPosition(): int
    {
        $sql = 'SELECT `position`
                FROM `s_article_configurator_groups`
                ORDER BY `position` DESC LIMIT 1';
        $position = $this->db->fetchOne($sql);

        return (int) ($position ? ++$position : 1);
    }

    private function getNextOptionPosition(int $groupId): int
    {
        $sql = 'SELECT `position`
                FROM `s_article_configurator_options`
                WHERE `group_id` = ?
                ORDER BY `position` DESC LIMIT 1';
        $position = $this->db->fetchOne($sql, $groupId);

        return (int) ($position ? ++$position : 1);
    }

    /**
     * Compares the given setId from the import file by name
     */
    private function compareSetIdByName(int $productId, int $setId): bool
    {
        $setName = 'Set-' . $this->getOrderNumber($productId);

        return $this->getSetIdBySetName($setName) === $setId;
    }

    /**
     * @param array<string, mixed> $configurator
     */
    private function updateConfiguratorSetTypeIfConfigSetIdIsNotEmptyAndSetDoesExistAndMatchSetName(int $productId, ?int $configuratorSetId, array $configurator): ?int
    {
        if (!$configuratorSetId && !empty($configurator['configSetId'])) {
            $configSetId = (int) $configurator['configSetId'];
            $setExists = $this->checkExistence('s_article_configurator_sets', $configSetId);
            $match = $this->compareSetIdByName($productId, $configSetId);
            if ($setExists && $match) {
                $configuratorSetId = $configSetId;
                $this->updateConfiguratorSet($configurator);
            }
        }

        return $configuratorSetId;
    }
}
