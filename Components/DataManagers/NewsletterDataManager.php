<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Newsletter\Group;
use Shopware\Models\Newsletter\Repository;
use SwagImportExport\Components\DataType\NewsletterDataType;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class NewsletterDataManager extends DataManager implements \Enlight_Hook
{
    private \Shopware_Components_Config $config;

    private Repository $groupRepository;

    /**
     * initialises the class properties
     */
    public function __construct(
        \Shopware_Components_Config $config,
        ModelManager $entityManager
    ) {
        $this->groupRepository = $entityManager->getRepository(Group::class);
        $this->config = $config;
    }

    public function supports(string $managerType): bool
    {
        return $managerType === DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER;
    }

    public function getDefaultFields(): array
    {
        return NewsletterDataType::$defaultFieldsForCreate;
    }

    /**
     * Return fields which should be set by default
     *
     * @return array<string>
     */
    public function getDefaultFieldsName(): array
    {
        $defaultFieldsForCreate = $this->getDefaultFields();

        return $this->getFields($defaultFieldsForCreate);
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, string|int> $record
     * @param array<string, mixed>      $defaultValues
     *
     * @return array<string, mixed>
     */
    public function setDefaultFieldsForCreate(array $record, array $defaultValues): array
    {
        foreach ($this->getDefaultFieldsName() as $key) {
            if (isset($record[$key])) {
                continue;
            }

            if (isset($defaultValues[$key])) {
                $record[$key] = $defaultValues[$key];
            }

            if ($key === 'groupName') {
                $record[$key] = $this->getGroupName($record['email'], $record[$key]);
            }
        }

        return $record;
    }

    /**
     * Returns newsletter default group name.
     *
     * @throws AdapterException
     */
    private function getGroupName(string $email, ?string $groupName): string
    {
        $group = $this->groupRepository->findOneBy(['name' => $groupName]);
        if ($group instanceof Group) {
            return $group->getName();
        }

        $groupId = $this->config->get('sNEWSLETTERDEFAULTGROUP');
        $group = $this->groupRepository->find($groupId);

        if (!$group instanceof Group) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/newsletter/group_required', 'Group is required for email %s');
            throw new AdapterException(\sprintf($message, $email));
        }

        return $group->getName();
    }
}
