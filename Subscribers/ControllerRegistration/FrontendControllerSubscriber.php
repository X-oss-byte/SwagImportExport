<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Subscribers\ControllerRegistration;

use Enlight\Event\SubscriberInterface;

class FrontendControllerSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    public function __construct(string $pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_SwagImportExport' => 'getSwagImportExport',
        ];
    }

    /**
     * Handles the Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaypalUnified event.
     * Returns the path to the frontend controller.
     */
    public function onGetUnifiedControllerPath(): string
    {
        return $this->pluginDirectory . '/Controllers/Frontend/SwagImportExport.php';
    }
}
