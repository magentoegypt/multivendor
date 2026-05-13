<?php
declare(strict_types=1);

namespace Vnecoms\VendorsCustomRegister\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class AttributeColumn extends Select
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory  = $collectionFactory;
    }
    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * @return \string[][]
     */
    private function getSourceOptions(): array
    {
        $options = $this->collectionFactory->create()
            ->addFieldToFilter("is_required", 1);

        $data = [];
        foreach ($options as $option) {
            $data[] = [
                'label' => $option->getData("attribute_code"),
                'value' => $option->getData("attribute_code")
            ] ;
        }
        return $data;
    }
}
