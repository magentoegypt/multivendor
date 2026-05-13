<?php
namespace Vnecoms\VendorsAvatarProfile\Controller\Avatar;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Remove extends \Magento\Framework\App\Action\Action
{
    /**
     * @var
     */
    protected $resultPageFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $session;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Filesystem\Driver
     */
    protected $driver;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data
     */
    protected $_helperData;

    /**
     * Remove constructor.
     * @param Context $context
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session $session
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem\Driver\File $driver
     * @param \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $session,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem\Driver\File $driver,
        \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData
    ) {
        $this->logger = $logger;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->session = $session;
        $this->customerFactory = $customerFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->driver = $driver;
        $this->_helperData = $_helperData;
        parent::__construct($context);
    }

    /**
     * @return array|bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @codingStandardsIgnoreStart
     */
    public function execute()
    {
        $customerId = $this->session->getCustomer()->getId();
        if ($customerId) {
            try{
                $target = $this->_mediaDirectory->getAbsolutePath("customer");
                $customer = $this->customerFactory->create()->load($customerId);
                if ($customer->getData('profile_picture')) {
                    $oldLink = $target.$customer->getData('profile_picture');
                    try{
                        $this->driver->deleteFile($oldLink);
                    }catch (\Magento\Framework\Exception\FileSystemException $e) {
                        $this->logger->debug($e->getMessage());
                    }
                    $customer->setProfilePicture(null)->getResource()->saveAttribute($customer, 'profile_picture');
                }

                $result = ['file' => null];
            } catch (\Magento\Framework\Exception\FileSystemException $e) {
                $result = ['error' => __("Something wrong. Please try upload again"), 'errorcode' => $e->getCode()];
                $this->logger->debug($e->getMessage());
            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
                $this->logger->debug($e->getMessage());
            }
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }

    /**
     * Retrieve path
     *
     * @param string $path
     * @param string $imageName
     *
     * @return string
     */
    protected function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }
}
