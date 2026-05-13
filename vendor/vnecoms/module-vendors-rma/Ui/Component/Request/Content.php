<?php
namespace Vnecoms\VendorsRMA\Ui\Component\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\AbstractComponent;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Registry;

class Content extends \Magento\Ui\Component\Form\Field
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    protected $_storeManager;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UiComponentInterface[] $components
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Registry $coreRegistry,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context,$uiComponentFactory , $components, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        $config = $this->getData('config');
        $config['ajax_url'] = $this->_getUrlAjax();
        $config['wysiwygConfigData']["content_css"] = $this->_getContentCss();
        //var_dump($config);exit;
        $this->setConfig($config);
    }

    /**
     * get custom css for wysiwyg tiny mce
     */
    protected function _getContentCss()
    {
        $css =  $this->_assetRepo->getUrl(
            'mage/adminhtml/wysiwyg/tiny_mce/themes/advanced/skins/default/content.css');
        $css .= ",".$this->_assetRepo->getUrl(
                'Vnecoms_VendorsRMA::wysiwyg/tiny_mce/blockquote.css');
        return $css;
    }
    /**
     * Get Ajax Url load html list
     */
    protected function _getUrlAjax()
    {
        $instance = \Magento\Framework\App\ObjectManager::getInstance();
        return $instance->create('\Magento\Backend\Helper\Data')->getUrl("vrma/request/qreponse");
    }

}
