<?php
/**
 * Created by PhpStorm.
 * User: camph
 * Date: 11/12/2018
 * Time: 11:30
 */

namespace Vnecoms\VendorsAvatarProfile\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ALLOW_CUSTOMER_AVATAR = 'vendors/profile/allow_customer_avatar';
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerRepository;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_frontUrlModel;
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;
    /**
     * @var \Magento\Framework\View\Element\BlockFactory
     */
    protected $_blockFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\View\Element\Template
     */
    protected $template;

    /**
     * Data constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\UrlInterface $frontUrlModel
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Element\Template $template
     * @param Context $context
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\UrlInterface $frontUrlModel,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\View\Element\Template $template,
        Context $context
    ) {
        $this->template = $template;
        $this->_storeManager = $storeManager;
        $this->_blockFactory = $blockFactory;
        $this->_assetRepo = $assetRepo;
        $this->vendorSession = $vendorSession;
        $this->_frontUrlModel = $frontUrlModel;
        $this->customerRepository = $customerFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomer()
    {
        return $this->customerRepository->create()->load($this->vendorSession->getCustomerId());
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return float|int
     */
    public function getMaxFileSize()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $size = $this->scopeConfig->getValue('customer/avatar/max_file_size', $storeScope);
        return $size * 1048576;
    }

    /**
     * @param $file
     * @return bool|string
     */
    public function getAttachmentUrl()
    {
        $file = $this->getCustomer()->getData("profile_picture");
        return $this->getAvatarOfCustomer($file);
    }

    /**
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttachmentVendorUrl()
    {
        $file = $this->getCustomer()->getData("profile_picture");
        return $this->getAvatarOfVendor($file);
    }

    /**
     * @param $file
     * @return bool|string
     */
    public function getAvatarOfCustomer($file)
    {
        $url = false;
        if ($file) {
            if (is_string($file)) {

                /*
                $url = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'customer' . $file;*/
                $url = $this->_storeManager->getStore()->getUrl(
                    'customer/avatar/viewfile',
                    ['image' => $this->urlEncoder->encode(ltrim($file, '/'))]
                );
            } elseif (!is_string($file)) {
                $url = $this->_assetRepo->getUrl("Vnecoms_VendorsAvatarProfile::images/no-profile-photo.png");
            }
        } elseif (!$file) {
            $url = $this->_assetRepo->getUrl("Vnecoms_VendorsAvatarProfile::images/no-profile-photo.png");
        }

        return $url;
    }

    /**
     * @param $file
     * @return bool|string
     */
    public function getAvatarOfVendor($file)
    {
        $url = false;
        if ($file) {
            if (is_string($file)) {

                /*
                $url = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'customer' . $file;*/
                $url = $this->_frontUrlModel->getUrl(
                    'avatar/index/viewfile',
                    ['image' => $this->urlEncoder->encode(ltrim($file, '/'))]
                );
            } elseif (!is_string($file)) {
                $url = $this->_assetRepo->getUrl("Vnecoms_VendorsAvatarProfile::images/no-profile-photo.png");
            }
        } elseif (!$file) {
            $url = $this->_assetRepo->getUrl("Vnecoms_VendorsAvatarProfile::images/no-profile-photo.png");
        }

        return $url;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function isAllowCustomerAvatar($storeId = null)
    {
        return $this->scopeConfig
            ->getValue(self::XML_PATH_ALLOW_CUSTOMER_AVATAR, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
