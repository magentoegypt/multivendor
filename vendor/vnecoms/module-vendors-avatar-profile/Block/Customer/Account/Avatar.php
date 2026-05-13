<?php

namespace Vnecoms\VendorsAvatarProfile\Block\Customer\Account;

use \Magento\Framework\View\Element\Template;

class Avatar extends Template
{
    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Vnecoms\Vendors\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Avatar constructor.
     * @param Template\Context $context
     * @param \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Vnecoms\Vendors\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     */
    public function __construct(
        Template\Context $context,
        \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\Vendors\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
    ) {
        $this->_helperData = $_helperData;
        $this->_objectManager = $objectManagerInterface;
        $this->customerSession = $customerSession;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getCurentAvatar()
    {
        return $this->_helperData->getAttachmentUrl();
    }

    /**
     * @return mixed
     */
    public function getMaxSizeUpload()
    {
        return $this->_helperData->getMaxFileSize();
    }

    /**
     * @return mixed
     */
    public function isAllowCustomerAvatar()
    {
        return $this->_helperData->isAllowCustomerAvatar();
    }

    public function isVendor()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->_collectionFactory->create()->addFieldToFilter('entity_id', $customerId);
        if (!$customer->getData()) {
            return true;
        } else {
            return false;
        }
    }
}
