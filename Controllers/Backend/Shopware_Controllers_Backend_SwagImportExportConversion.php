<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use Shopware\Components\Model\Exception\ModelNotFoundException;
use SwagImportExport\Models\Expression;
use SwagImportExport\Models\ExpressionRepository;
use SwagImportExport\Models\Profile;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Backend_SwagImportExportConversion extends \Shopware_Controllers_Backend_ExtJs
{
    public function getConversionsAction(): void
    {
        $profileId = $this->Request()->getParam('profileId');
        $filter = $this->Request()->getParam('filter', []);

        $manager = $this->getModelManager();

        $expressionRepository = $manager->getRepository(Expression::class);
        \assert($expressionRepository instanceof ExpressionRepository);

        $filter = \array_merge(['p.id' => $profileId], $filter);

        $query = $expressionRepository->getExpressionsListQuery(
            $filter,
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit') ? (int) $this->Request()->getParam('limit') : null,
            $this->Request()->getParam('start') ? (int) $this->Request()->getParam('start') : null,
        )->getQuery();

        $count = $manager->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $count,
        ]);
    }

    public function createConversionAction(): void
    {
        $profileId = $this->Request()->getParam('profileId');
        $data = $this->Request()->getParam('data', 1);

        $manager = $this->getModelManager();
        $profileEntity = $manager->getRepository(Profile::class)->findOneBy(['id' => $profileId]);
        if (!$profileEntity instanceof Profile) {
            throw new ModelNotFoundException(Profile::class, $profileId);
        }

        $expressionEntity = new Expression();

        $expressionEntity->setProfile($profileEntity);
        $expressionEntity->setVariable($data['variable']);
        $expressionEntity->setExportConversion($data['exportConversion']);
        $expressionEntity->setImportConversion($data['importConversion']);

        $manager->persist($expressionEntity);
        $manager->flush();

        $this->View()->assign([
            'success' => true,
            'data' => [
                'id' => $expressionEntity->getId(),
                'profileId' => $profileEntity->getId(),
                'exportConversion' => $expressionEntity->getExportConversion(),
                'importConversion' => $expressionEntity->getImportConversion(),
            ],
        ]);
    }

    public function updateConversionAction(): void
    {
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = [$data];
        }

        $manager = $this->getModelManager();
        $expressionRepository = $manager->getRepository(Expression::class);

        try {
            foreach ($data as $expression) {
                $expressionEntity = $expressionRepository->findOneBy(['id' => $expression['id']]);
                if (!$expressionEntity instanceof Expression) {
                    continue;
                }
                $expressionEntity->setVariable($expression['variable']);
                $expressionEntity->setExportConversion($expression['exportConversion']);
                $expressionEntity->setImportConversion($expression['importConversion']);
                $manager->persist($expressionEntity);
            }

            $manager->flush();

            $this->View()->assign(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'message' => $e->getMessage(), 'data' => $data]);
        }
    }

    public function deleteConversionAction(): void
    {
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = [$data];
        }

        $manager = $this->getModelManager();
        $expressionRepository = $manager->getRepository(Expression::class);

        try {
            foreach ($data as $expression) {
                $expressionEntity = $expressionRepository->findOneBy(['id' => $expression['id']]);
                if ($expressionEntity instanceof Expression) {
                    $manager->remove($expressionEntity);
                }
            }

            $manager->flush();

            $this->View()->assign(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'message' => $e->getMessage(), 'data' => $data]);
        }
    }

    protected function initAcl(): void
    {
        $this->addAclPermission('getConversions', 'export', 'Insufficient Permissions (getConversions)');
        $this->addAclPermission('createConversion', 'export', 'Insufficient Permissions (createConversion)');
        $this->addAclPermission('updateConversion', 'export', 'Insufficient Permissions (updateConversion)');
        $this->addAclPermission('deleteConversion', 'export', 'Insufficient Permissions (deleteConversion)');
    }
}
