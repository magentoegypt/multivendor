<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Blocks extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * Render page containers.
     */
    public function renderPageContainers()
    {
        /* @var $app \Vnecoms\VendorsCms\Model\App */
        $app = $this->_initApp();
        $layout = $this->getRequest()->getParam('layout');
        $selected = $this->getRequest()->getParam('selected', null);
        $blocksChooser = $this->_view->getLayout()->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container'
        )->setValue(
            $selected
        )->setLayoutHandle(
            $layout
        )->setAllowedContainers(
            $app->getSupportedContainers()
        );
        $this->setBody($blocksChooser->toHtml());
    }

    /**
     * Blocks Action (Ajax request).
     */
    public function execute()
    {
        $this->_objectManager->get(
            'Magento\Framework\App\State'
        )->emulateAreaCode(
            'frontend',
            [$this, 'renderPageContainers']
        );
    }
}
