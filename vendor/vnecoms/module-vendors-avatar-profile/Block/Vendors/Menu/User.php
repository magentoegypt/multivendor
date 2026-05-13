<?php
namespace Vnecoms\VendorsAvatarProfile\Block\Vendors\Menu;

class User extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data
     */
    protected $_helperData;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData,
        array $data = []
    ) {
        $this->vendorSession = $vendorSession;
        $this->_helperData = $_helperData;
        return parent::__construct($context, $data);
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
    public function getCurentAvatar()
    {
        return $this->_helperData->getAttachmentUrl();
    }

    /**
     * Get the name of currently logged in vendor
     *
     * @return string
     */
    public function getVendorName()
    {
        return $this->vendorSession->getCustomer()->getName();
    }
}
