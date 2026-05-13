<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product Chooser for "Product Link" Cms Widget Plugin.
 */

namespace Vnecoms\VendorsCms\Block\App;

/**
 * Class Conditions.
 */
class Conditions extends \Magento\CatalogWidget\Block\Product\Widget\Conditions
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Rule\Rule
     */
    protected $vendorRule;

    /**
     * Conditions constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\CatalogWidget\Model\Rule $rule
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\VendorsCms\Model\Rule\Rule $vendorRule
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCms\Model\Rule\Rule $vendorRule,
        array $data = []
    ) {
        $this->vendorRule = $vendorRule;
        parent::__construct($context, $elementFactory, $conditions, $rule, $registry, $data);
    }

    /**
     * @var string
     */
    protected $_template = 'app/product/conditions.phtml';

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        /*
         * @var \Vnecoms\VendorsCms\Model\App
         */
        $app = $this->registry->registry('current_app');
        if ($app) {
            $appParameters = $app->getParameters();
            if (isset($appParameters['conditions'])) {
                $this->vendorRule->loadPost($appParameters);
            }
        }
    }

    /**
     * @return string
     */
    public function getNewChildUrl()
    {
        return $this->getUrl(
            'cms/rule_product/conditions/form/'.$this->getElement()->getContainer()->getHtmlId()
        );
    }

    /**
     * @return string
     */
    public function getInputHtml()
    {
        $this->input = $this->elementFactory->create('text');
        $this->vendorRule->getConditions()->setJsFormObject($this->getHtmlId());
        $this->input->setRule($this->vendorRule)->setRenderer($this->conditions);

        return $this->input->toHtml();
    }
}
