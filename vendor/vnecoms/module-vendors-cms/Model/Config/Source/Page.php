<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Config\Source;

use Vnecoms\VendorsCms\Model\ResourceModel\Page\CollectionFactory;

/**
 * Class Page.
 */
class Page implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * To option array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->collectionFactory->create()->addVendorFilter($this->getVendor())->toOptionIdArray();
        }
        array_unshift($this->options, ['value' => '', 'label' => __('-- Please select a Page --')]);

        return $this->options;
    }

    public function getVendor()
    {
        if (null === $this->vendorSession) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }
}
