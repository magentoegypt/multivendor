<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form\Fieldset;

/**
 * Data provider for categories field of product page.
 */
class ProductFormCategories extends AbstractModifier
{
    /**#@+
     * Group values
     */
    const GROUP_CATEGORY_NAME = 'additional_data';
    const GROUP_CATEGORY_SCOPE = 'product';
    const CONTAINER_HEADER_NAME = 'container_header';

    /**
     * @var LocatorInterface
     * @since 101.0.0
     */
    protected $locator;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var DbHelper
     */
    protected $dbHelper;

    /**
     * @var array
     */
    protected $categoriesTrees = [];

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @var array
     * @since 101.0.0
     */
    protected $meta = [];


    /**
     * ProductFormCategories constructor.
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param DbHelper $dbHelper
     * @param UrlInterface $urlBuilder
     * @param ArrayManager $arrayManager
     * @param LocatorInterface $locator
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        DbHelper $dbHelper,
        UrlInterface $urlBuilder,
        ArrayManager $arrayManager,
        LocatorInterface $locator
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->dbHelper = $dbHelper;
        $this->urlBuilder = $urlBuilder;
        $this->arrayManager = $arrayManager;
        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $this->createVendorCategoriesPanel();
        return $this->meta;
    }

    /**
     * Create "Customizable Options" panel
     *
     * @return $this
     * @since 101.0.0
     */
    protected function createVendorCategoriesPanel()
    {
        $this->meta[static::GROUP_CATEGORY_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Categories'),
                        'componentType' => Fieldset::NAME,
                        'dataScope' => static::GROUP_CATEGORY_SCOPE,
                        'collapsible' => false,
                    ],
                ],
            ],
            'children' => [
                static::CONTAINER_HEADER_NAME => $this->customizeCategoriesField(10)
            ]
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        if (isset($data[$productId]['product']['ves_category_ids']))
        $data[$productId]['product']['ves_category_ids'] = explode(",", $data[$productId]['product']['ves_category_ids']);
        return $data;
    }


    /**
     * @param $categorySortOrder
     * @return array
     */
    protected function customizeCategoriesField($categorySortOrder)
    {
        return
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Vendor Categories'),
                            'dataScope' => '',
                            'breakLine' => false,
                            'formElement' => 'container',
                            'componentType' => 'container',
                            'component' => 'Magento_Ui/js/form/components/group',
                            'sortOrder' => $categorySortOrder,
                            'additionalClasses' => 'admin__field-clean',
                            // 'scopeLabel' => __('[GLOBAL]'),
                        ],
                    ],
                ],
                'children' => [
                    "ves_category_ids" => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'formElement' => 'select',
                                    'componentType' => 'field',
                                    'component' => 'Vnecoms_VendorsCategory/js/components/new-category',
                                    'filterOptions' => true,
                                    'chipsEnabled' => true,
                                    'disableLabel' => true,
                                    'levelsVisibility' => '2',
                                    'elementTmpl' => 'Vnecoms_VendorsCategory/ui-select',
                                    //   'elementTmpl' => 'ui/grid/filters/elements/ui-select',
                                    'options' => $this->getCategoriesTree(),
                                    'listens' => [
                                        'index=ves_create_category:responseData' => 'setParsed',
                                        'newOption' => 'toggleOptionSelected',
                                    ],
                                    'config' => [
                                        'dataScope' => "ves_category_ids",
                                        'sortOrder' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ];
    }

    /**
     * Retrieve categories tree.
     *
     * @param string|null $filter
     *
     * @return array
     */
    protected function getCategoriesTree($filter = null)
    {
        if (isset($this->categoriesTrees[$filter])) {
            return $this->categoriesTrees[$filter];
        }

        /* @var $matchingNamesCollection \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection */
        $matchingNamesCollection = $this->categoryCollectionFactory->create();

        if ($filter !== null) {
            $matchingNamesCollection->addFieldToFilter(
                'name',
                ['like' => $this->dbHelper->addLikeEscape($filter, ['position' => 'any'])]
            );
        }

        $matchingNamesCollection
            ->addFieldToFilter('category_id', ['neq' => $this->getRootId()])
            ->addFieldToFilter('vendor_id', $this->getVendor()->getId());

        $shownCategoriesIds = [];

        /** @var \Vnecoms\VendorsCategory\Model\Category $category */
        foreach ($matchingNamesCollection as $category) {
            foreach (explode('/', $category->getPath()) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }
        /* @var $collection \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addFieldToFilter('category_id', ['in' => array_keys($shownCategoriesIds)]);

        $categoryById = [
            $this->getRootId() => [
                'value' => $this->getRootId(),
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['value' => $categoryId];
                }
            }

            $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getId()]['parent'] = $category->getParentId();
            $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
        }

        $this->categoriesTrees[$filter] = $categoryById[$this->getRootId()]['optgroup'];

        return $this->categoriesTrees[$filter];
    }

    /**
     * get Root ID.
     *
     * @return int
     */
    public function getRootId()
    {
        return 0;
    }

    /**
     * @return mixed
     */
    public function getRootVendorId()
    {
        $category = \Magento\Framework\App\ObjectManager::getInstance()->create(
            'Vnecoms\VendorsCategory\Model\Category'
        )->getRootId($this->getVendor()->getId());

        return $category;
    }

    /**
     * @return mixed
     */
    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }
}
