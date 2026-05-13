<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Model;

use Magento\Framework\Registry;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Config;
use Vnecoms\Vendors\Model\VendorFactory;
use Vnecoms\VendorsConfig\Helper\Data as ConfigHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;
use Vnecoms\Vendors\Helper\Data as VendorHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class LoadProduct
{

  /**
   * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
   */
  protected $_productCollectionFactory;

  /**
   * @var \Magento\Catalog\Model\Config
   */
  protected $catalogConfig;

  /**
   * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
   */
  protected $_productCollection;

  /**
   * Catalog product visibility
   *
   * @var \Magento\Catalog\Model\Product\Visibility
   */
  protected $productVisibility;

  /**
   * @var \Vnecoms\Vendors\Model\VendorFactory
   */
  protected $_vendorFactory;

  /**
   * @var ConfigHelper
   */
  protected $_configHelper;

  /**
   * @var \Magento\MediaStorage\Helper\File\Storage\Database
   */
  protected $_fileStorageDatabase;

  /**
   * @var \Magento\Framework\Filesystem\Directory\ReadInterface
   */
  protected $mediaDirectory;

  /**
   * @var \Vnecoms\Vendors\Helper\Image
   */
  protected $_imageHelper;

  /**
   * @var \Vnecoms\VendorsProduct\Helper\Data
   */
  protected $_productHelper;

  /**
   * @var \Vnecoms\Vendors\Helper\Data
   */
  protected $_vendorHelper;

  /**
   * @var \Magento\Cms\Model\Template\Filter
   */
  protected $_filter;

  /**
   * @var \Vnecoms\VendorsSales\Model\ResourceModel\OrderFactory
   */
  protected $_orderResourceFactory;

  /**
   * @var \Magento\Checkout\Helper\Cart
   */
  protected $_cartHelper;

  /**
   * @var \Magento\Framework\Url\EncoderInterface
   */
  protected $urlEncoder;

  /**
   * @var \Magento\Framework\Data\Form\FormKey
   */
  protected $formKey;

  /**
   * The list of loaded vendors
   * @var array
   */
  protected $_vendors = [];

  /**
   * @var \Magento\Framework\Pricing\PriceCurrencyInterface
   */
  protected $priceCurrency;


  /**
   * Url Builder
   *
   * @var \Magento\Framework\UrlInterface
   */
  protected $_urlBuilder;

  /**
   * [protected description]
   * @var [type]
   */
  protected $assetRepo;

  /**
   * [protected description]
   * @var [type]
   */
  protected $request;

  /**
   * Design
   *
   * @var \Magento\Framework\View\DesignInterface
   */
  protected $_design;

  /**
   * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
   */
  protected $_localeDate;

  /**
   * [protected description]
   * @var [type]
   */
  protected $stockFilter;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\Process
     */
  protected $process;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
  protected $_mediaDirectory;

  /**
   * [__construct description]
   * @param CollectionFactory                                 $productCollectionFactory [description]
   * @param Visibility                                        $productVisibility        [description]
   * @param Config                                            $catalogConfig            [description]
   * @param VendorFactory                                     $vendorFactory            [description]
   * @param ConfigHelper                                      $configHelper             [description]
   * @param MagentoMediaStorageHelperFileStorageDatabase      $fileStorageDatabase      [description]
   * @param VnecomsVendorsHelperImage                         $imageHelper              [description]
   * @param ProductHelper                                     $productHelper            [description]
   * @param VendorHelper                                      $vendorHelper             [description]
   * @param MagentoCmsModelTemplateFilter                     $filter                   [description]
   * @param VnecomsVendorsSalesModelResourceModelOrderFactory $orderResourceFactory     [description]
   * @param MagentoCheckoutHelperCart                         $cartHelper               [description]
   * @param MagentoFrameworkUrlEncoderInterface               $urlEncoder               [description]
   * @param MagentoFrameworkDataFormFormKey                   $formKey                  [description]
   * @param MagentoFrameworkPricingPriceCurrencyInterface     $priceCurrency            [description]
   * @param MagentoFrameworkFilesystem                        $filesystem               [description]
   * @param MagentoFrameworkUrlInterface                      $urlBuilder               [description]
   * @param MagentoFrameworkAppRequestInterface               $request                  [description]
   * @param MagentoFrameworkViewAssetRepository               $assetRepo                [description]
   * @param MagentoFrameworkViewDesignInterface               $_design                  [description]
   * @param MagentoFrameworkStdlibDateTimeTimezoneInterface   $localeDate               [description]
   * @param MagentoCatalogInventoryHelperStock                $stockFilter              [description]
   */
  public function __construct(
      CollectionFactory $productCollectionFactory,
      Visibility $productVisibility,
      Config $catalogConfig,
      VendorFactory $vendorFactory,
      ConfigHelper $configHelper,
      \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase,
      \Vnecoms\Vendors\Helper\Image $imageHelper,
      ProductHelper $productHelper,
      VendorHelper $vendorHelper,
      \Magento\Cms\Model\Template\Filter $filter,
      \Vnecoms\VendorsSales\Model\ResourceModel\OrderFactory $orderResourceFactory,
      \Magento\Checkout\Helper\Cart $cartHelper,
      \Magento\Framework\Url\EncoderInterface $urlEncoder,
      \Magento\Framework\Data\Form\FormKey $formKey,
      \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
      \Magento\Framework\Filesystem $filesystem,
      \Magento\Framework\UrlInterface $urlBuilder,
      \Magento\Framework\App\RequestInterface $request,
      \Magento\Framework\View\Asset\Repository $assetRepo,
      \Magento\Framework\View\DesignInterface $_design,
      \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
      \Magento\CatalogInventory\Helper\Stock $stockFilter,
      \Vnecoms\VendorsPriceComparison\Model\Process $process
  ) {
      $this->_productCollectionFactory = $productCollectionFactory;
      $this->productVisibility = $productVisibility;
      $this->_productHelper = $productHelper;
      $this->catalogConfig = $catalogConfig;
      $this->_vendorFactory = $vendorFactory;
      $this->_configHelper = $configHelper;
      $this->_fileStorageDatabase = $fileStorageDatabase;
      $this->_mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
      $this->_imageHelper = $imageHelper;
      $this->_vendorHelper = $vendorHelper;
      $this->_filter = $filter;
      $this->_orderResourceFactory = $orderResourceFactory;
      $this->_cartHelper = $cartHelper;
      $this->urlEncoder = $urlEncoder;
      $this->formKey = $formKey;
      $this->priceCurrency = $priceCurrency;
      $this->_urlBuilder = $urlBuilder;
      $this->assetRepo = $assetRepo;
      $this->request = $request;
      $this->_design = $_design;
      $this->_localeDate = $localeDate;
      $this->stockFilter = $stockFilter;
      $this->process = $process;
  }

  /**
   * Get product collection
   *
   * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
   */
  public function getProductCollection($dataPost){
      if ($this->_productCollection === null) {
          $this->_productCollection = $this->_productCollectionFactory->create();
          $this->_productCollection->addAttributeToSelect('vendor_id')
              ->addAttributeToFilter('select_from_product_id', $dataPost['parentProductId'])
              ->addAttributeToFilter('approval',['in' => $this->_productHelper->getAllowedApprovalStatus()]);

          $this->_productCollection->addAttributeToSelect($this->catalogConfig->getProductAttributes())
              ->addMinimalPrice()
              ->addFinalPrice()
              ->addTaxPercents()
              ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
          $this->stockFilter->addInStockFilterToCollection($this->_productCollection);
      }

      return $this->_productCollection;
  }

  /**
   * Get products
   *
   * @return multitype:unknown
   */
  public function getProductComparison($dataPost){
      $products = [];
      foreach($this->getProductCollection($dataPost) as $product){
          $vendorId = $product->getVendorId();
          $hasCheckVailable = false;

          if ($product->getTypeId() == Configurable::TYPE_CODE) {
              $childProducts = $product->getTypeInstance()->getUsedProducts($product);
              if ($childProducts) {
                  foreach ($childProducts as $childs) {
                      if ($this->process->checkProductSalesEnable($childs, false)) {
                          $hasCheckVailable = true;
                      }
                  }
              }
          } else {
              $hasCheckVailable = $this->process->checkProductSalesEnable($product, true, true);
          }

          if(!$vendorId || !$hasCheckVailable) continue;
          $productData = $this->_prepareProductData($product, $dataPost);
          if($productData === false) continue;
          if($productData['pc_vendor']['status'] != \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED) continue;
          $products[] = $productData;
      }

      return $products;
  }

  /**
   * [_prepareProductData description]
   * @param  MagentoCatalogModelProduct $product  [description]
   * @param  [type]                     $dataPost [description]
   * @return [type]                               [description]
   */
  protected function _prepareProductData(\Magento\Catalog\Model\Product $product, $dataPost){
      $vendorId = $product->getVendorId();
      if(!$vendorId) return false;
      $productData = $product->getData();
      $productData['final_price'] = $this->priceCurrency->convert($product->getFinalPrice());
      $productData['min_price'] = $this->priceCurrency->convert($product->getMinPrice());
      $productData['minimal_price'] = $this->priceCurrency->convert($product->getMinimalPrice());
      $productData['max_price'] = $this->priceCurrency->convert($product->getMaxPrice());
      $productData['price'] = $this->priceCurrency->convert($product->getPrice());

      if (isset($dataPost["attributesConfigurable"])) {
        $_children = $product->getTypeInstance()->getUsedProducts($product);
        if ($_children) {
          $childProduct = false;
          foreach ($_children as $child) {
              if (!$this->process->checkProductSalesEnable($child, false)) continue;
              $checkChild = true;
              foreach ($dataPost["attributesConfigurable"] as $attribute) {
                 if ($child->getData($attribute['attribute']) != $attribute['value']) {
                    $checkChild = false;
                    break;
                 }
              }
              if ($checkChild) {
                $childProduct = $child;
                break;
              }
          }
          if (!$childProduct) return false;
          $productData['sku'] = $childProduct->getSku();
          $productData['final_price'] = $this->priceCurrency->convert($childProduct->getFinalPrice());
          $productData['min_price'] = $this->priceCurrency->convert($childProduct->getMinPrice());
          $productData['minimal_price'] = $this->priceCurrency->convert($childProduct->getMinimalPrice());
          $productData['max_price'] = $this->priceCurrency->convert($childProduct->getMaxPrice());
          $productData['price'] = $this->priceCurrency->convert($childProduct->getPrice());
          $productData['attributesConfigurable'] = $dataPost["attributesConfigurable"];
        }
      }

      if(!isset($this->_vendors[$vendorId])){
          $vendor = $this->_vendorFactory->create();
          $vendor->load($vendorId);
          if(!$vendor->getId()) return false;

          /* Add vendor home page URL if the homepage is installed*/
          if(class_exists('Vnecoms\VendorsPage\Helper\Data')){
              $om = \Magento\Framework\App\ObjectManager::getInstance();
              $helper = $om->create('Vnecoms\VendorsPage\Helper\Data');
              $vendor->setData('pc_home_page',$helper->getUrl($vendor));
          }
          $vendor->setData('pc_title',$this->_configHelper->getVendorConfig('general/store_information/name', $vendorId));
          $vendor->setData('pc_description',$this->_configHelper->getVendorConfig('general/store_information/short_description', $vendorId));
          $vendor->setData('pc_logo_url',$this->getLogoUrl($vendor));
          $vendor->setData('pc_logo_width',$this->getLogoWidth());
          $vendor->setData('pc_logo_height',$this->getLogoHeight());
          $vendor->setData('pc_address',$this->getAddress($vendor));
          $vendor->setData('pc_country_name', $vendor->getCountryName($this->_design->getLocale()));
          $vendor->setData('pc_sales_count',$this->getSalesCount($vendor));
          $vendor->setData(
              'pc_joined_date',
              $this->formatDate($vendor->getCreatedAt(),\IntlDateFormatter::MEDIUM)
          );

          $this->_vendors[$vendorId] = $vendor;
      }

      $productData['pc_product_url'] = $product->getProductUrl();
      $productData['pc_addtocart_url'] = $this->getAddToCartUrl($product);
      $productData['pc_vendor'] = $this->_vendors[$vendorId]->getData();

      return $productData;
  }

  /**
   * Keep Transparency logo
   *
   * @return boolean
   */
  public function keepTransparencyLogo(){
      return true;
  }

  /**
   * Get logo width
   *
   * @return int
   */
  public function getLogoWidth(){
      return 75;
  }

  /**
   * Get logo height
   *
   * @return int
   */
  public function getLogoHeight(){
      return 75;
  }

  /**
   * Get Logo URL by vendor
   *
   * @param \Vnecoms\Vendors\Model\Vendor $vendor
   * @return string
   */
  public function getLogoUrl(\Vnecoms\Vendors\Model\Vendor $vendor){
      $scopeConfig = $this->_configHelper->getVendorConfig(
          'general/store_information/logo',
          $vendor->getId()
      );
      $basePath = 'ves_vendors/logo/';
      $path =  $basePath. $scopeConfig;


      if ($scopeConfig && $this->checkIsFile($path)) {
          $this->_imageHelper->init($scopeConfig)
              ->setBaseMediaPath($basePath)
              ->keepTransparency($this->keepTransparencyLogo())
              ->backgroundColor([250,250,250])
              ->resize($this->getLogoWidth(),$this->getLogoHeight());
          return $this->_imageHelper->getUrl();
      }

      return $this->_getViewFileUrl('Vnecoms_Vendors::images/no-logo.jpg');
  }

  /**
   * Retrieve url of a view file
   *
   * @param string $fileId
   * @param array $params
   * @return string
   */
  protected function _getViewFileUrl($fileId, array $params = [])
  {
      try {
          $params = array_merge(['_secure' => $this->request->isSecure()], $params);
          return $this->assetRepo->getUrlWithParams($fileId, $params);
      } catch (\Magento\Framework\Exception\LocalizedException $e) {
          return false;
      }
  }

  /**
   * If DB file storage is on - find there, otherwise - just file_exists
   *
   * @param string $filename relative file path
   * @return bool
   */
  protected function checkIsFile($filename)
  {
      if ($this->_fileStorageDatabase->checkDbUsage() && !$this->_mediaDirectory->isFile($filename)) {
          $this->_fileStorageDatabase->saveFileToFilesystem($filename);
      }
      return $this->_mediaDirectory->isFile($filename);
  }

  /**
   * Get vendor address
   *
   * @param \Vnecoms\Vendors\Model\Vendor $vendor
   * @return string
   */
  public function getAddress(\Vnecoms\Vendors\Model\Vendor $vendor){
      $template = $this->_vendorHelper->getAddressTemplate();
      $country =  $vendor->getCountryName(
          $this->_design->getLocale()
      );
      $variables = [
          'street' => $vendor->getStreet(),
          'city' => $vendor->getCity(),
          'country' => $country,
          'region' => $vendor->getRegion(),
          'postcode' => $vendor->getPostcode(),
      ];
      return $this->_filter->setVariables($variables)->filter($template);
  }

  /**
   * Get sales count
   *
   * @param \Vnecoms\Vendors\Model\Vendor $vendor
   * @return number
   */
  public function getSalesCount(\Vnecoms\Vendors\Model\Vendor $vendor){
      $resource = $this->_orderResourceFactory->create();
      return $resource->getSalesCount($vendor->getId());
  }

  /**
   * Retrieve url for direct adding product to cart
   *
   * @param \Magento\Catalog\Model\Product $product
   * @param array $additional
   * @return string
   */
  public function getAddToCartUrl($product, $additional = [])
  {
      if ($this->request->getParam('wishlist_next')) {
          $additional['wishlist_next'] = 1;
      }

      $addUrlKey = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;
      $addUrlValue = $this->_urlBuilder->getUrl('*/*/*', ['_use_rewrite' => true, '_current' => true]);
      $additional[$addUrlKey] = $this->urlEncoder->encode($addUrlValue);
      $additional['form_key'] = $this->formKey->getFormKey();
      $additional['in_cart'] = false;
      return $this->_cartHelper->getAddUrl($product, $additional);
  }

  /**
   * Retrieve formatting date
   *
   * @param null|string|\DateTimeInterface $date
   * @param int $format
   * @param bool $showTime
   * @param null|string $timezone
   * @return string
   */
  public function formatDate(
      $date = null,
      $format = \IntlDateFormatter::SHORT,
      $showTime = false,
      $timezone = null
  ) {
      $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
      return $this->_localeDate->formatDateTime(
          $date,
          $format,
          $showTime ? $format : \IntlDateFormatter::NONE,
          null,
          $timezone
      );
  }
}
