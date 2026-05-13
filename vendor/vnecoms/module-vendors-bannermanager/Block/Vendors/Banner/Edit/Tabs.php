<?php
namespace Vnecoms\BannerManager\Block\Vendors\Banner\Edit;

use Magento\Framework\Module\Manager;
/**
 * Class Tabs Grid
 * 
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Tabs extends \Vnecoms\Vendors\Block\Vendors\Widget\Tabs
{
    /** @var Manager  */
    protected $_moduleManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        Manager $moduleManager,
        array $data = []
    ){
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->_moduleManager = $moduleManager;
    }

    /**
     * parent constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('backend_banner_edit_tabs'); //name in layout tab declare xml
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Banner Information'));
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->addTab('main_section', array(
            'label'     => __('Banner Information'),
            'title'     => __('Banner Information'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab\Main')
                ->toHtml(),
            'active'    => true
        ));

        $this->addTab('items_section', array(
            'label'     => __('Banner Items'),
            'title'     => __('Banner Items'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab\Items')
                ->toHtml(),
        ));

        $this->addTab('configuration_section', array(
            'label'     => __('Configuration'),
            'title'     => __('Configuration'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab\Configuration')
                ->toHtml(),
        ));

        if ($this->getRequest()->getParam('banner_id') && $this->vendorsCmsEnabled()) {
            $this->addTabAfter('implementcode_section', array(
                'label' => __('Implementation Code'),
                'title' => __('Implementation Code'),
                'content' => $this->getLayout()->createBlock('Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab\Implementcode')->setTemplate('Vnecoms_BannerManager::implementcode.phtml')->toHtml(),
            ), 'configuration_section');
        }

        return $this;

    }

    /**
     * @return bool
     */
    protected function vendorsCmsEnabled()
    {
        return $this->_moduleManager->isEnabled('Vnecoms_VendorsCms');
    }
}

