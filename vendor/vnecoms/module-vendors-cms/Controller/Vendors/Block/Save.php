<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Block;

use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\VendorsCms\Model\Block;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Vnecoms\VendorsCms\Controller\Vendors\Block
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::block_action_save';
    /**
     * Save action.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $block = $this->_initBlock();

        if (!$block) {
            //return $resultRedirect->setPath('cms/*/new', ['_current' => true, 'id' => null]);
        }

        $data = $this->getRequest()->getPostValue();
        $isNewBlock = !isset($data['block_id']);

        if ($data) {
            $id = $this->getRequest()->getParam('block_id');

            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = Block::STATUS_ENABLED;
            }
            if (empty($data['block_id'])) {
                $data['block_id'] = null;
            }
            if (!empty($this->getVendor()->getId()) || $data['vendor_id']) {
                $data['vendor_id'] = $this->getVendor()->getId();
            }

            /** @var \Vnecoms\VendorsCms\Model\Block $model */
            $model = $this->_objectManager->create('Vnecoms\VendorsCms\Model\Block')->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addError(__('This block no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved the block.'));
                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['block_id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the block.'));
            }

            $this->_getSession()->setFormData($data);

            return $resultRedirect->setPath('*/*/edit', ['block_id' => $this->getRequest()->getParam('block_id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
