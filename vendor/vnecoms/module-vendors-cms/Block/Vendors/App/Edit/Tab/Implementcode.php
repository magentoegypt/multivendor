<?php

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab;

use Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;

/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 23/01/2017
 * Time: 23:29.
 */
class Implementcode extends Generic implements TabInterface
{
    /** @var \Vnecoms\VendorsCms\Model\ResourceModel\App\Collection  */
    protected $appCollection;

    /** @var  \Vnecoms\Vendors\Model\Vendor */
    protected $vendorSession;

    /** @var \Vnecoms\VendorsCms\Model\AppFactory  */
    protected $appFactory;

    /**
     * @var \Magento\Widget\Helper\Conditions
     */
    protected $conditionsHelper;

    /**
     * Implementcode constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\VendorsCms\Model\ResourceModel\App\Collection $appCollection
     * @param \Vnecoms\VendorsCms\Model\AppFactory $appFactory
     * @param \Magento\Widget\Helper\Conditions $conditions
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsCms\Model\ResourceModel\App\Collection $appCollection,
        \Vnecoms\VendorsCms\Model\AppFactory $appFactory,
        \Magento\Widget\Helper\Conditions $conditions,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->appCollection = $appCollection;
        $this->appFactory = $appFactory;
        $this->conditionsHelper = $conditions;
    }

    /**
     * @return mixed
     */
    public function getCurrentApp()
    {
        $appId = $this->getRequest()->getParam('app_id');
        if ($this->_coreRegistry->registry('app_id') && $appId) {
            return $this->getModel()->load($appId);
        }

        return isset($appId) ? $this->getModel()->load($appId) : $this->getModel();
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->getCurrentApp()->getParameters();
    }

    /**
     * @return string
     */
    public function getAppStringEncode()
    {
        return '{{app app_id="'.$this->getCurrentApp()->getId().'"}}';
        $string = '{{app type="'.$this->getCurrentApp()->getType().'" ';
        foreach ($this->getParameters() as $name => $value) {
            if ($name == 'conditions') {
                $name = 'conditions_encoded';
                $value = $this->conditionsHelper->encode($value);
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }
            if ($name && strlen((string) $value)) {
                $string .= $name.'="'.$this->_escaper->escapeHtml(
                    $value
                ).'" ';
            }
        }
        $string .= '}}';

        return $string;
    }

    /**
     * @return mixed
     */
    public function getApps()
    {
        return $this->appCollection->create();
    }

    /**
     * @return \Vnecoms\VendorsCms\Model\App
     */
    public function getModel()
    {
        return $this->appFactory->create();
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession == null) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Implementation Code');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Implementation Code');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
