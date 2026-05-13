<?php
namespace Vnecoms\RMA\Ui\Component\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\AbstractComponent;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Registry;

class Order extends \Magento\Ui\Component\Form\Field
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    protected $_storeManager;

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
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
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
        //var_dump($config);exit;
        if ($order = $this->_coreRegistry->registry("current_order")) {
            $config['value'] = $order->getIncrementId();
        }
        $config['ajax_url'] = $this->_getUrlAjax();
        $config['is_show'] = $this->_isShow();
        $this->setConfig($config);
    }

    /**
     * Get Ajax Url load customer list
     */
    protected function _getUrlAjax()
    {
        $instance = \Magento\Framework\App\ObjectManager::getInstance();
        return $instance->create('\Magento\Backend\Helper\Data')->getUrl("vrma/request/loadItem");
    }

    /*
    * Check show or hidden button view when have order_id
    */
    protected function _isShow()
    {
        return "display:none;margin-top:10px";
    }
}
