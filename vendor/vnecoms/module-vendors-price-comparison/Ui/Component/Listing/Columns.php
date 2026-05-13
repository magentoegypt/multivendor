<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Ui\Component\Listing;

class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * Default columns max order
     */
    const DEFAULT_COLUMNS_MAX_ORDER = 100;

    /** @var \Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface */
    protected $attributeRepository;

    /**
     * @var array
     */
    protected $filterMap = [
        'default' => 'text',
        'select' => 'select',
        'boolean' => 'select',
        'multiselect' => 'select',
        'date' => 'dateRange',
    ];
    
    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Catalog\Ui\Component\ColumnFactory $columnFactory
     * @param \Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface $attributeRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Catalog\Ui\Component\ColumnFactory $columnFactory,
        \Vnecoms\VendorsPriceComparison\Ui\Component\Listing\Attribute\Repository $attributeRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        
        $this->columnFactory = $columnFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $columnSortOrder = self::DEFAULT_COLUMNS_MAX_ORDER;
        foreach ($this->attributeRepository->getList() as $attribute) {
            $config = [
                'label' => $attribute->getFrontendLabel(),
                'add_field' => true,
                'visible' => true,
                'sortOrder' => ++$columnSortOrder,
                'filter' => $this->getFilterType($attribute->getFrontendInput()),
            ];

            $column = $this->columnFactory->create($attribute, $this->getContext(), $config);
            $column->prepare();
            $this->addComponent($attribute->getAttributeCode(), $column);
        }
        parent::prepare();
    }

    /**
     * Retrieve filter type by $frontendInput
     *
     * @param string $frontendInput
     * @return string
     */
    protected function getFilterType($frontendInput)
    {
        return isset($this->filterMap[$frontendInput]) ? $this->filterMap[$frontendInput] : $this->filterMap['default'];
    }
}
