<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Controller\Adminhtml\Field;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\Form\SaveHandler;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Save for save checkout fields
 */
class Save extends Action
{
    public const ADMIN_RESOURCE = 'Amasty_CheckoutCore::checkout_settings_fields';

    /**
     * @var SaveHandler
     */
    private $saveHandler;

    public function __construct(
        Context $context,
        SaveHandler $saveHandler
    ) {
        $this->saveHandler = $saveHandler;
        parent::__construct($context);
    }

    public function execute()
    {
        $fields = $this->getRequest()->getParam('field');

        if (!is_array($fields)) {
            return $this->resultRedirectFactory->create()
                ->setPath('*/*', ['_current' => true]);
        }

        try {
            $storeId = $this->getRequest()->getParam(ScopeInterface::SCOPE_STORE, Field::DEFAULT_STORE_ID);
            $this->saveHandler->execute($fields, $storeId);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()
                ->setPath('*/*', ['_current' => true]);
        }

        $this->messageManager->addSuccessMessage(__('Fields information has been successfully updated'));

        return $this->resultRedirectFactory->create()
            ->setPath('*/*', ['_current' => true]);
    }
}
