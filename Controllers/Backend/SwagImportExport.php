<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\CustomModels\ImportExport\Logger;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Backend_SwagImportExport extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'downloadFile',
        ];
    }

    public function uploadFileAction()
    {
        /** @var UploadedFile $file */
        $file = $this->Request()->files->get('fileId');

        if (!$file->isValid()) {
            return $this->View()->assign(['success' => false, 'message' => $file->getErrorMessage()]);
        }

        $clientOriginalName = $file->getClientOriginalName();

        if (!preg_match('/^[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/', $clientOriginalName)) {
            return $this->View()->assign(['success' => false, 'message' => 'No valid file name']);
        }

        $extension = $file->getClientOriginalExtension();

        if (!$this->isFormatValid($extension) || \in_array($extension, Shopware_Controllers_Backend_MediaManager::$fileUploadBlacklist, true)) {
            return $this->View()->assign(['success' => false, 'message' => 'No valid file format']);
        }

        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');
        $file->move($uploadPathProvider->getPath(), $clientOriginalName);

        $this->view->assign([
            'success' => true,
            'data' => [
                'path' => $uploadPathProvider->getRealPath($clientOriginalName),
                'fileName' => $clientOriginalName,
            ],
        ]);
    }

    /**
     * Fires when the user want to open a generated order document from the backend order module.
     *
     * Returns the created pdf file with an echo.
     */
    public function downloadFileAction()
    {
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');

        try {
            $fileName = $this->Request()->getParam('fileName');

            if ($fileName === null) {
                throw new \Exception('File name must be provided');
            }

            $filePath = $uploadPathProvider->getRealPath($fileName);

            $extension = $uploadPathProvider->getFileExtension($fileName);
            switch ($extension) {
                case 'csv':
                    $application = 'text/csv';
                    break;
                case 'xml':
                    $application = 'application/xml';
                    break;
                default:
                    throw new \Exception('File extension is not valid');
            }

            if (\file_exists($filePath)) {
                $this->View()->assign(
                    [
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'File not exist',
                    ]
                );
            }

            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Front()->Plugins()->Json()->setRenderer(false);

            $response = $this->Response();
            $response->clearHeaders();
            $response->headers->replace();

            $response->setHeader('Cache-Control', 'public');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename=' . $fileName);
            $response->setHeader('Content-Type', $application);
            $response->sendHeaders();

            \readfile($filePath);
            exit;
        } catch (\Exception $e) {
            $this->View()->assign(
                [
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $e->getMessage(),
                ]
            );

            return;
        }
    }

    public function getLogsAction()
    {
        /** @var \Shopware\CustomModels\ImportExport\Repository $loggerRepository */
        $loggerRepository = $this->getModelManager()->getRepository(Logger::class);

        $query = $loggerRepository->getLogListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit', 25),
            $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->getModelManager()->createPaginator($query);

        // returns the total count of the query
        $total = $paginator->count();

        // returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $total,
        ]);
    }

    /**
     * Registers acl permissions for controller actions
     */
    public function initAcl()
    {
        $this->addAclPermission('uploadFile', 'import', 'Insuficient Permissions (uploadFile)');
        $this->addAclPermission('downloadFile', 'export', 'Insuficient Permissions (downloadFile)');
    }

    /**
     * Check is file format valid
     *
     * @param string $extension
     *
     * @return bool
     */
    private function isFormatValid($extension)
    {
        switch ($extension) {
            case 'csv':
            case 'xml':
                return true;
            default:
                return false;
        }
    }
}
