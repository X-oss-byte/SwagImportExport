<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use Doctrine\ORM\AbstractQuery;
use SwagImportExport\Components\Utils\DataHelper;
use SwagImportExport\Models\Session;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Backend_SwagImportExportSession extends \Shopware_Controllers_Backend_ExtJs
{
    private \Shopware_Components_Snippet_Manager $snippetManager;

    public function __construct(
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->snippetManager = $snippetManager;
    }

    public function getSessionDetailsAction(): void
    {
        $manager = $this->getModelManager();
        $sessionId = $this->Request()->getParam('sessionId');

        if ($sessionId === null) {
            $this->View()->assign(['success' => false, 'message' => 'No session found']);

            return;
        }
        $sessionModel = $manager->getRepository(Session::class)->find($sessionId);

        if (!$sessionModel instanceof Session) {
            $this->View()->assign(['success' => false, 'message' => 'No session found']);

            return;
        }

        $dataSet = [
            'fileName' => $sessionModel->getFileName(),
            'type' => $sessionModel->getType(),
            'profile' => $sessionModel->getProfile() ? $sessionModel->getProfile()->getName() : '',
            'dataset' => $sessionModel->getTotalCount(),
            'position' => $sessionModel->getPosition(),
            'fileSize' => DataHelper::formatFileSize((int) $sessionModel->getFileSize()),
            'userName' => $sessionModel->getUserName(),
            'date' => $sessionModel->getCreatedAt()->format('d.m.Y H:i'),
            'status' => $sessionModel->getState(),
        ];

        $result = $this->translateDataSet($dataSet);

        $this->View()->assign(['success' => true, 'data' => $result]);
    }

    public function getSessionsAction(): void
    {
        $manager = $this->get('models');
        $query = $manager->getRepository(Session::class)->getSessionsListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            (int) $this->Request()->getParam('limit', 25),
            (int) $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        // returns the total count of the query
        $total = $paginator->count();

        // returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        foreach ($data as $key => $row) {
            $data[$key]['fileUrl'] = \urlencode($row['fileName']);
            $data[$key]['fileName'] = $row['fileName'];
            $data[$key]['fileSize'] = DataHelper::formatFileSize($row['fileSize']);
        }

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $total,
        ]);
    }

    /**
     * Deletes a single order from the database.
     * Expects a single order id which placed in the parameter id
     */
    public function deleteSessionAction(): void
    {
        $manager = $this->getModelManager();
        try {
            $data = $this->Request()->getParam('data');

            if (\is_array($data) && isset($data['id'])) {
                $data = [$data];
            }

            foreach ($data as $record) {
                $sessionId = $record['id'];

                if (empty($sessionId) || !\is_numeric($sessionId)) {
                    $this->View()->assign([
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'No valid Id',
                    ]);

                    return;
                }

                $entity = $manager->getRepository(Session::class)->find($sessionId);
                if ($entity instanceof Session) {
                    $manager->remove($entity);
                }
            }

            // Performs all the collected actions.
            $manager->flush();

            $this->View()->assign([
                'success' => true,
                'data' => $this->Request()->getParams(),
            ]);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function initAcl(): void
    {
        $this->addAclPermission('getSessions', 'read', 'Insufficient Permissions (getSessions)');
        $this->addAclPermission('deleteSession', 'export', 'Insufficient Permissions (deleteSession)');
    }

    /**
     * @param array<string, string|int|null> $data
     *
     * @return array<string, mixed>
     */
    private function translateDataSet(array $data): array
    {
        $namespace = $this->snippetManager->getNamespace('backend/swag_import_export/session_data');
        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $namespace->get($key, $key)] = $namespace->get((string) $value, (string) $value);
        }

        return $result;
    }
}
