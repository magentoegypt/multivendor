<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Ui\Component\Product\Form\Categories;

use Magento\Framework\Data\OptionSourceInterface;
use Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Vnecoms\VendorsCategory\Model\Category as CategoryModel;

/**
 * Options tree for "Categories" field.
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $categoriesTree;

    protected $_vendorSession;

    /**
     * @var CategoryModel
     */
    protected $categoryModel;

    /**
     * Options constructor.
     *
     * @param CategoryCollectionFactory      $categoryCollectionFactory
     * @param RequestInterface               $request
     * @param \Vnecoms\Vendors\Model\Session $session
     * @param CategoryModel                  $category
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        RequestInterface $request,
        \Vnecoms\Vendors\Model\Session $session,
        \Vnecoms\VendorsCategory\Model\Category $category
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->request = $request;
        $this->_vendorSession = $session;
        $this->categoryModel = $category;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getCategoriesTree();
    }

    /**
     * Retrieve categories tree.
     *
     * @return array
     */
    protected function getCategoriesTree()
    {
        if ($this->categoriesTree === null) {
           // $rootId = $this->categoryModel->getRootId($this->getVendor()->getId());
            $rootId = 0; //included root category
            $categoryById = [
                $rootId => [
                    'value' => $rootId,
                ],
            ];

            /**
             * @var \Vnecoms\VendorsCategory\Helper\Data $helper
             */
            $helper = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\VendorsCategory\Helper\Data');




            /* @var $collection \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection */
            $collection = $this->categoryCollectionFactory->create();

            $collection->addFieldToFilter('vendor_id', $this->getVendor()->getId())
                ->addFieldToFilter('is_active', 1);


            foreach ($collection as $category) {
                if (!$helper->getEnabledVendorSubCat()) {
                    if ($category->getLevel() >= 1) {
                        continue;
                    }
                }


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

            $this->categoriesTree = isset($categoryById[$rootId]['optgroup']) ? $categoryById[$rootId]['optgroup'] : [];
        }

        return $this->categoriesTree;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_vendorSession->getVendor();
    }
}
