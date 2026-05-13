<?php

namespace Vnecoms\BannerManager\Controller\Vendors;

/**
 * Abstract Class AbstractAction
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
abstract class AbstractAction extends \Vnecoms\Vendors\Controller\Vendors\Action
{

    /** @var \Magento\Backend\Helper\Js  */
    protected $_jsHelper;
    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $_storeManager;
    /** @var \Magento\Backend\Model\View\Result\ForwardFactory  */
    protected $_resultForwardFactory;
    /** @var \Magento\Framework\View\Result\LayoutFactory  */
    protected $_resultLayoutFactory;

    /** @var \Magento\Framework\Controller\Result\RedirectFactory  */
    protected $_resultRedirectFactory;

    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $_resultPageFactory;
    /** @var \Magento\Framework\App\Response\Http\FileFactory  */
    protected $_fileFactory;
    /** @var \Vnecoms\BannerManager\Model\BannerFactory  */
    protected $_bannerFactory;
    /** @var \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory  */
    protected $_bannerCollectionFactory;

    /** @var \Vnecoms\BannerManager\Model\ItemFactory  */
    protected $_itemFactory;

    /** @var \Vnecoms\BannerManager\Model\ResourceModel\Item\CollectionFactory  */
    protected $_itemCollectionFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $_localeDate;

    /** @var \Magento\Framework\Filesystem\Driver\File  */
    protected $_file;

    /** @var \Magento\Framework\Filesystem  */
    protected $_fileSystem;
    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $jsonFactory;

    /** @var \Magento\Ui\Component\MassAction\Filter  */
    protected $filter;


    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $fileSystem,

        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,

        \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory,
        \Vnecoms\BannerManager\Model\ItemFactory $itemFactory,
        \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory $bannerCollectionFactory,
        \Vnecoms\BannerManager\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Ui\Component\MassAction\Filter $filter
    ){
        parent::__construct($context);
        $this->_coreRegistry            = $context->getCoreRegsitry();
        $this->_jsHelper                = $jsHelper;
        $this->_storeManager            = $storeManager;
        $this->_fileFactory             = $fileFactory;
        $this->_fileSystem              = $fileSystem;
        $this->_resultPageFactory       = $resultPageFactory;
        $this->_resultLayoutFactory     = $resultLayoutFactory;
        $this->_resultRedirectFactory   = $context->getResultRedirectFactory();
        $this->_resultForwardFactory    = $resultForwardFactory;
        $this->_bannerFactory           = $bannerFactory;
        $this->_itemFactory             = $itemFactory;
        $this->_bannerCollectionFactory = $bannerCollectionFactory;
        $this->_itemCollectionFactory   = $itemCollectionFactory;
        $this->_localeDate              = $localeDate;
        $this->_file                    = $file;
        $this->jsonFactory              = $jsonFactory;
        $this->filter                   = $filter;
    }
}
