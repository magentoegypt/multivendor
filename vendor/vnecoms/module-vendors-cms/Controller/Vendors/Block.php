<?php


namespace Vnecoms\VendorsCms\Controller\Vendors;

use Vnecoms\Vendors\Controller\Vendors\Action;

abstract class Block extends Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::block';


    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * Block constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context $context
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return bool|mixed
     */
    protected function _initBlock()
    {
        $blockId = $this->getRequest()->getParam('block_id', false);
        $blockModel = $this->_objectManager->create('\Vnecoms\VendorsCms\Model\Block');

        if ($blockId) {
            $blockModel->load($blockId);

            if ($blockModel->getVendorId() != $this->getVendor()->getId()) {
                return false;
            }
        }

        $this->_coreRegistry->register('cms_block', $blockModel);
        $this->_coreRegistry->register('current_block', $blockModel);

        return $blockModel;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Session
     */
    public function getVendorSession()
    {
        return $this->vendorSession;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = $this->_objectManager->get('Vnecoms\Vendors\Model\Session');

            return $this->vendorSession->getVendor();
        }
    }
}
