<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use Shopware\Components\Model\Exception\ModelNotFoundException;
use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Service\Struct\ProfileDataStruct;
use SwagImportExport\Models\Profile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileService implements ProfileServiceInterface
{
    private ModelManager $modelManager;

    private Filesystem $fileSystem;

    private \Enlight_Components_Snippet_Manager $snippetManager;

    public function __construct(ModelManager $manager, Filesystem $filesystem, \Enlight_Components_Snippet_Manager $snippetManager)
    {
        $this->modelManager = $manager;
        $this->fileSystem = $filesystem;
        $this->snippetManager = $snippetManager;
    }

    /**
     * {@inheritdoc}
     */
    public function importProfile(UploadedFile $file): void
    {
        $namespace = $this->snippetManager->getNamespace('backend/swag_import_export/controller');

        if (\strtolower($file->getClientOriginalExtension()) !== 'json') {
            $this->fileSystem->remove($file->getPathname());

            throw new \Exception($namespace->get('swag_import_export/profile/profile_import_no_json_error'));
        }

        $content = \file_get_contents($file->getPathname());

        if (empty($content)) {
            $this->fileSystem->remove($file->getPathname());

            throw new \Exception($namespace->get('swag_import_export/profile/profile_import_no_data_error'));
        }
        $profileData = (array) \json_decode($content);

        if (empty($profileData['name'])
            || empty($profileData['type'])
            || empty($profileData['tree'])
        ) {
            $this->fileSystem->remove($file->getPathname());

            throw new \Exception($namespace->get('swag_import_export/profile/profile_import_no_valid_data_error'));
        }

        try {
            $profile = new Profile();
            $profile->setName($profileData['name']);
            $profile->setType($profileData['type']);
            $profile->setTree(\json_encode($profileData['tree'], \JSON_THROW_ON_ERROR));

            $this->modelManager->persist($profile);
            $this->modelManager->flush($profile);
            $this->fileSystem->remove($file->getPathname());
        } catch (\Exception $e) {
            $this->fileSystem->remove($file->getPathname());

            $message = $e->getMessage();
            $msg = $namespace->get('swag_import_export/profile/profile_import_error');

            if (\strpbrk('Duplicate entry', $message) !== false) {
                $msg = $namespace->get('swag_import_export/profile/profile_import_duplicate_error');
            }

            throw new \Exception($msg);
        }
    }

    public function exportProfile(int $profileId): ProfileDataStruct
    {
        $profile = $this->modelManager->getRepository(Profile::class)->findOneBy(['id' => $profileId]);
        if (!$profile instanceof Profile) {
            throw new ModelNotFoundException(Profile::class, $profileId);
        }

        return new ProfileDataStruct($profile);
    }
}
