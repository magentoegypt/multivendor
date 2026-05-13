<?php

namespace Vnecoms\RMA\Block\Frontend\Customer\NewRequest;

use Magento\Sales\Model\Order;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Price amount renderer
 *
 * @method bool getIncludeContainer()
 */

class Info extends \Magento\Framework\View\Element\Template
{
    const PACKOPEDED_YES    = 1;
    const PACKOPEDED_NO     = 0;
    const TYPE_REFUND       = 'refund';
    const TYPE_REPLACE      = 'replace';

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_saleOrder;
    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_config;
    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Reason\Collection
     */
    protected $_reason;

    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Request\Collection
     */
    protected $_requestCollection;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Info constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order $order
     * @param \Vnecoms\RMA\Helper\Config $config
     * @param \Vnecoms\RMA\Model\ResourceModel\Reason\Collection $reasonCollection
     * @param \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order $order,
        \Vnecoms\RMA\Helper\Config $config,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\RMA\Model\ResourceModel\Reason\Collection $reasonCollection,
        \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection,
        array $data = []
    ) {
        $this->priceCurrency        = $priceCurrency;
        $this->_customerSession     = $customerSession;
        $this->_saleOrder           = $order;
        $this->_config              = $config;
        $this->_reason              = $reasonCollection;
        $this->_coreRegistry        = $coreRegistry;
        $this->_requestCollection   = $requestCollection->create();
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Request RMA'));
    }

    /**
     * @return $this
     */
    public function getOrderByCustomerId()
    {
//        echo $this->_storeManager->getStore();exit;
        $customerId = $this->_customerSession->getCustomerId();
        $orders = $this->_saleOrder
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter("state", ["IN"=>[Order::STATE_COMPLETE,Order::STATE_PROCESSING]]);
        return $orders;
    }

    /**
     * Format price value
     *
     * @param float $amount
     * @param bool $includeContainer
     * @param int $precision
     * @return float
     */
    public function formatCurrency(
        $amount,
        $includeContainer = true,
        $precision = PriceCurrencyInterface::DEFAULT_PRECISION
    ) {
//        echo $amount;exit;
        return $this->priceCurrency->convertAndFormat($amount, $includeContainer, $precision);
    }

    public function getRequestData()
    {
        $request = $this->_customerSession->getRequestData();
        $data = isset($request)? $request : '';
        return $data;
    }
    /**
     * get custom css for wysiwyg tiny mce
     */
    public function getContentCss()
    {
        $css =  $this->_assetRepo->getUrl(
            'mage/adminhtml/wysiwyg/tiny_mce/themes/advanced/skins/default/content.css'
        );
        $css .= ",".$this->_assetRepo->getUrl(
            'Vnecoms_RMA::wysiwyg/tiny_mce/blockquote.css'
        );
        return $css;
    }


    public function getHtmlEditor($message)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $wysiwygConfig = $object_manager->get('\Magento\Cms\Model\Wysiwyg\Config');
        $configwysiwyg =  $wysiwygConfig->getConfig();
        $configwysiwygData = $configwysiwyg->getData();
        $configwysiwygData["settings"]["theme_advanced_buttons1"] = "bold,italic,|,justifyleft,justifycenter,justifyright,|,fontselect,fontsizeselect,|,forecolor,backcolor,|,link,unlink,image,|,bullist,numlist,|,code";
        $configwysiwygData["settings"]["theme_advanced_buttons2"] = false;
        $configwysiwygData["settings"]["theme_advanced_buttons3"] = false;
        $configwysiwygData["settings"]["theme_advanced_buttons4"] = false;
        $configwysiwygData["settings"]["theme_advanced_statusbar_location"] = false;
        $configwysiwygData["content_css"] = $this->getContentCss();
        $configwysiwygData["height"] = "250px";
        $configwysiwygData["add_variables"] = false;
        $configwysiwygData["plugins"] = false;
        $configwysiwygData["add_widgets"] = false;
        $configwysiwygData["add_images"] = false;
        $configwysiwygData["files_browser_window_url"] =false;
        $configwysiwygData["no_display"] =true;
        $configwysiwygData["toggle_button"] = false;
        $configwysiwyg->setData($configwysiwygData);
        $elementId = "content_message_reply";
        $config = [
            'label'     => __('Message'),
            'name'      => 'message',
            'config' => $configwysiwyg,
            'wysiwyg' =>  true,
            'style' => 'width:100%; height:250px;',
            'required'=> true,
            'class' => " required-entry",
            'value' => $message,
            "validation" => [
                "required-entry" => true
            ]
        ];
        $form = $object_manager->get('\Magento\Framework\Data\Form');
        $editor = $object_manager->get('\Magento\Framework\Data\Form\Element\Editor')->setData($config);
        $editor->setForm($form);
        $editor->setId($elementId);
        return $editor->getElementHtml();
    }
    /**
     * @return array
     */
    public function getPackOpened()
    {
        return [
            self::PACKOPEDED_YES    => __('Yes'),
            self::PACKOPEDED_NO     => __('No')
        ];
    }

    /**
     * @return array
     */
    public function getType()
    {
        return [
            self::TYPE_REFUND       => __('Refund'),
            self::TYPE_REPLACE      => __('Replace')
        ];
    }

    /**
     * @return \Vnecoms\RMA\Model\ResourceModel\Reason\Collection
     */
    public function getReason()
    {
        return $this->_reason->setOrder("sort_order", "ASC");
    }

    /**
     * @return \Vnecoms\RMA\Helper\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }
    /**
     * get Url load product from order Id
     * @return mixed
     */
    public function getUrlFindProduct()
    {
        return $this->getUrl('vrma/customer/ajaxproduct');
    }
}
