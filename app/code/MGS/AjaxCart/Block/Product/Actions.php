<?php
namespace MGS\AjaxCart\Block\Product;

use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;

/**
 * Class Actions
 * @package MGS\AjaxCart\Block\Product
 */
class Actions extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Registry $coreRegistry,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->coreRegistry = $coreRegistry;
        $this->formKey = $formKey;
        $this->jsonHelper = ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->coreRegistry->registry('current_product');
        return parent::_toHtml();
    }
    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->jsonHelper->serialize($this->formKey->getFormKey());
    }
}
