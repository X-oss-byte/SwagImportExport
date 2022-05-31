<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;
use SwagImportExport\Components\DbAdapters\Results\ArticleWriterResult;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\Articles\ConfiguratorValidator;

class ConfiguratorWriter
{
    protected ConfiguratorValidator $configuratorValidator;

    private DbalHelper $dbalHelper;

    private Connection $connection;

    private \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    private array $sets;

    public function __construct(
        DbalHelper $dbalHelper,
        Connection $connection,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db
    ) {
        $this->dbalHelper = $dbalHelper;
        $this->connection = $connection;
        $this->sets = $this->getSets();
        $this->db = $db;
        $this->configuratorValidator = new ConfiguratorValidator();
    }

    /**
     * @param array<string, mixed> $configuratorData
     *
     * @throws AdapterException
     */
    public function writeOrUpdateConfiguratorSet(ArticleWriterResult $articleWriterResult, array $configuratorData)
    {
        $configuratorSetId = null;

        foreach ($configuratorData as $configurator) {
            if (!$this->isValid($configurator)) {
                continue;
            }
            $configurator = $this->configuratorValidator->filterEmptyString($configurator);
            $this->configuratorValidator->validate($configurator, ConfiguratorValidator::$mapper);

            /**
             * Updates the type of a configurator set
             */
            $configuratorSetId = $this->updateConfiguratorSetTypeIfConfigSetIdIsNotEmptyAndSetDoesExistAndMatchSetName($articleWriterResult->getArticleId(), $configuratorSetId, $configurator);

            if (!$configuratorSetId) {
                $configuratorSetId = $this->getConfiguratorSetIdByArticleId($articleWriterResult->getArticleId());
            }

            if (!$configuratorSetId) {
                if (empty($configurator['configSetName'])) {
                    $orderNumber = $this->getOrderNumber($articleWriterResult->getArticleId());
                    $dataSet['name'] = 'Set-' . $orderNumber;
                } else {
                    $dataSet['name'] = $configurator['configSetName'];
                }

                $dataSet['public'] = false;
                $dataSet['id'] = $configurator['configSetId'];
                if ($configurator['configSetType']) {
                    $dataSet['type'] = $configurator['configSetType'];
                }

                if (\array_key_exists($dataSet['name'], $this->sets)) {
                    $configuratorSetId = $this->sets[$dataSet['name']];
                } else {
                    $configuratorSetId = $this->createSet($dataSet);
                    $this->sets[$dataSet['name']] = $configuratorSetId;
                }
            }

            if ($articleWriterResult->getMainDetailId() != $articleWriterResult->getDetailId()) {
                $this->updateArticleSetsRelation($articleWriterResult->getArticleId(), $configuratorSetId);
            }

            /*
             * configurator option
             */
            if (isset($configurator['configOptionId']) && !empty($configurator['configOptionId'])) {
                $optionResult = $this->getOptionRow($configurator['configOptionId']);

                $optionId = $optionResult['id'];
                $groupId = $optionResult['group_id'];

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
                if (isset($configurator['configOptionPosition']) && !empty($configurator['configOptionPosition'])) {
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

            $this->updateOptionRelation($articleWriterResult->getDetailId(), $optionId);
            $this->updateSetOptionRelation($configuratorSetId, $optionId);

            unset($groupId);
            unset($optionId);
        }
    }

    /**
     * @return int
     */
    public function getSetIdBySetName(string $name)
    {
        return $this->sets[$name];
    }

    /**
     * @return string|bool
     */
    public function getGroupIdByGroupName(string $name)
    {
        $sql = 'SELECT `id`
                FROM s_article_configurator_groups
                WHERE `name` = ?';

        return $this->connection->fetchColumn($sql, [$name]);
    }

    /**
     * @return string
     */
    public function getOptionIdByOptionNameAndGroupId(string $optionName, int $groupId)
    {
        $sql = 'SELECT `id`
                FROM s_article_configurator_options
                WHERE `name` = ? AND `group_id` = ?';

        return $this->db->fetchOne($sql, [$optionName, $groupId]);
    }

    public function getOptionRow(int $id)
    {
        $sql = 'SELECT `id`, `group_id`, `name`, `position`
                FROM s_article_configurator_options
                WHERE `id` = ?';

        return $this->db->fetchRow($sql, [$id]);
    }

    protected function updateArticleSetsRelation(int $articleId, int $setId)
    {
        $this->db->query('UPDATE s_articles SET configurator_set_id = ? WHERE id = ?', [$setId, $articleId]);
    }

    /**
     * @throws DBALException
     */
    protected function updateGroupsRelation(int $setId, int $groupId)
    {
        $sql = "INSERT INTO s_article_configurator_set_group_relations (set_id, group_id)
                VALUES ($setId, $groupId)
                ON DUPLICATE KEY UPDATE set_id=VALUES(set_id), group_id=VALUES(group_id)";

        $this->connection->exec($sql);
    }

    /**
     * @throws DBALException
     */
    protected function updateOptionRelation(int $articleDetailId, int $optionId)
    {
        $sql = "INSERT INTO s_article_configurator_option_relations (article_id, option_id)
                VALUES ($articleDetailId, $optionId)
                ON DUPLICATE KEY UPDATE article_id=VALUES(article_id), option_id=VALUES(option_id)";

        $this->connection->exec($sql);
    }

    /**
     * @throws DBALException
     */
    protected function updateSetOptionRelation(int $setId, int $optionId)
    {
        $sql = "INSERT INTO s_article_configurator_set_option_relations (set_id, option_id)
                VALUES ($setId, $optionId)
                ON DUPLICATE KEY UPDATE set_id=VALUES(set_id), option_id=VALUES(option_id)";

        $this->connection->exec($sql);
    }

    /**
     * @return array
     */
    protected function getSets()
    {
        $sets = [];
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_article_configurator_sets');

        foreach ($result as $row) {
            $sets[$row['name']] = $row['id'];
        }

        return $sets;
    }

    protected function getConfiguratorSetIdByArticleId(int $articleId)
    {
        $result = $this->db->fetchRow('SELECT configurator_set_id FROM s_articles WHERE id = ?', [$articleId]);

        return $result['configurator_set_id'];
    }

    /**
     * @return string
     */
    protected function createSet(array $data)
    {
        // Delete id to avoid unique constraint violations
        unset($data['id']);

        $builder = $this->dbalHelper
            ->getQueryBuilderForEntity($data, Set::class, null);
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return string
     */
    protected function createGroup(array $data)
    {
        $builder = $this->dbalHelper
            ->getQueryBuilderForEntity($data, Group::class, null);
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return string
     */
    protected function createOption(array $data)
    {
        $builder = $this->dbalHelper
            ->getQueryBuilderForEntity($data, Option::class, null);
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    protected function getOrderNumber(int $articleId)
    {
        $sql = 'SELECT `ordernumber`
                FROM s_articles_details
                WHERE kind = 1 AND articleID = ?';

        return $this->connection->fetchColumn($sql, [$articleId]);
    }

    /**
     * This function updates a specific database record for a configurator set.
     *
     * @param array<string, mixed> $configurator
     */
    private function updateConfiguratorSet(array $configurator)
    {
        $sql = 'UPDATE s_article_configurator_sets SET
                type=:setType
                WHERE id=:id';

        $this->db->executeQuery($sql, ['setType' => $configurator['configSetType'], 'id' => $configurator['configSetId']]);
    }

    /**
     * @param array<string, mixed> $configurator
     *
     * @return bool
     */
    private function isValid(array $configurator)
    {
        if (!isset($configurator['configOptionId']) || empty($configurator['configOptionId'])) {
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

    /**
     * @return bool
     */
    private function checkExistence(string $table, int $id)
    {
        $sql = "SELECT `id` FROM $table WHERE id = ?";
        $result = $this->connection->fetchColumn($sql, [$id]);

        return $result ? true : false;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws AdapterException
     *
     * @return mixed|string
     */
    private function getConfiguratorGroup(array $data)
    {
        if (isset($data['configGroupId'])) {
            if ($this->checkExistence('s_article_configurator_groups', $data['configGroupId'])) {
                $groupId = $data['configGroupId'];
            }
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
                $this->groups[$groupData['name']] = $groupId;
            }
        }

        if (!$groupId) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/provide_groupname_groupid', 'Please provide groupname or groupId');
            throw new AdapterException($message);
        }

        return $groupId;
    }

    /**
     * @return int
     */
    private function getNextGroupPosition()
    {
        $sql = 'SELECT `position`
                FROM `s_article_configurator_groups`
                ORDER BY `position` DESC LIMIT 1';
        $position = $this->db->fetchOne($sql);
        $position = $position ? ++$position : 1;

        return $position;
    }

    /**
     * @return int
     */
    private function getNextOptionPosition(int $groupId)
    {
        $sql = 'SELECT `position`
                FROM `s_article_configurator_options`
                WHERE `group_id` = ?
                ORDER BY `position` DESC LIMIT 1';
        $position = $this->db->fetchOne($sql, $groupId);
        $position = $position ? ++$position : 1;

        return $position;
    }

    /**
     * Compares the given setId from the import file by name
     *
     * @return bool
     */
    private function compareSetIdByName(int $articleId, int $setId)
    {
        $setName = 'Set-' . $this->getOrderNumber($articleId);

        return $this->getSetIdBySetName($setName) == $setId;
    }

    /**
     * @param array<string, mixed> $configurator
     *
     * @return int
     */
    private function updateConfiguratorSetTypeIfConfigSetIdIsNotEmptyAndSetDoesExistAndMatchSetName(int $articleId, ?int $configuratorSetId, array $configurator)
    {
        if (!$configuratorSetId && isset($configurator['configSetId']) && !empty($configurator['configSetId'])) {
            $setExists = $this->checkExistence('s_article_configurator_sets', $configurator['configSetId']);
            $match = $this->compareSetIdByName($articleId, $configurator['configSetId']);
            if ($setExists && $match) {
                $configuratorSetId = $configurator['configSetId'];
                $this->updateConfiguratorSet($configurator);
            }
        }

        return $configuratorSetId;
    }
}
