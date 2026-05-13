<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Template extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * Templates Chooser Action (Ajax request).
     */
    public function execute()
    {
        /* @var $widgetInstance \Magento\Widget\Model\Widget\Instance */
        $app = $this->_initApp();
        $block = $this->getRequest()->getParam('block');
        $selected = $this->getRequest()->getParam('selected', null);
        $templateChooser = $this->_view->getLayout()->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Template'
        )->setSelected(
            $selected
        )->setTemplates(
            $app->getSupportedTemplatesByContainer($block)
        );
        $this->setBody($templateChooser->toHtml());
    }
}
