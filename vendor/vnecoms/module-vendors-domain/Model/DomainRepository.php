<?php

namespace Vnecoms\VendorsDomain\Model;

use Magento\Framework\Exception\LocalizedException;
use Vnecoms\VendorsDomain\Model\Config\Source\Url as SourceUrl;

/**
 * Vendor repository.
 */
class DomainRepository implements \Vnecoms\VendorsDomain\Api\DomainRepositoryInterface
{
    const CONFIG_PATH = "page/base_url/url_type";

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * @var ResourceModel\Domain
     */
    protected $resourceModel;

    /**
     * @var DomainFactory
     */
    protected $domainFactory;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Vnecoms\VendorsConfig\Model\Config
     */
    protected $vendorConfig;

    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $helperData;

    /**
     * DomainRepository constructor.
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param DomainFactory $domainFactory
     * @param ResourceModel\Domain $resource
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig
     * @param \Vnecoms\VendorsDomain\Helper\Data $helperData
     */
    public function __construct(
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsDomain\Model\DomainFactory $domainFactory,
        \Vnecoms\VendorsDomain\Model\ResourceModel\Domain $resource,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig,
        \Vnecoms\VendorsDomain\Helper\Data $helperData
    ) {
        $this->vendorFactory               = $vendorFactory;
        $this->domainFactory = $domainFactory;
        $this->resourceModel = $resource;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->vendorConfig = $vendorConfig;
        $this->helperData = $helperData;
    }

    /**
     * @param \Vnecoms\VendorsDomain\Api\Data\DomainInterface $domain
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     * @throws LocalizedException
     */
    public function save(\Vnecoms\VendorsDomain\Api\Data\DomainInterface $domain)
    {
        $domainId = $domain->getId();

        $data = $this->dataObjectProcessor->buildOutputDataArray(
            $domain,
            \Vnecoms\VendorsDomain\Api\Data\DomainInterface::class
        );

        if (!isset($data["type"])
            || !in_array($data["type"], ["domain", "sub_domain"])
        ) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('type', "domain or sub_domain");
        }

        if (!isset($data["domain"])) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('domain', "");
        }

        if (!isset($data["status"])) {
            $data["status"] = Domain::STATUS_APPROVED;
        }

        $data["value"] = $data["domain"];

        $mainDomain = $this->helperData->getMainDomain();
        $domainUrl = $data["type"] == SourceUrl::URL_SUB_DOMAIN?$data["value"].'.'.$mainDomain:$data["value"];
        $data["domain"] = $domainUrl;

        $domainObject = $this->domainFactory->create();
        $domainObject->setData($data);

        if ($domainId) {
            $domainExits = $this->domainFactory->create()->load($domainId);
            $mergedData = array_merge($domainExits->getData(), $data);
            $domainExits->setData($mergedData);
            $domainObject = $domainExits;
        }

        if ($domain->getVendorId() && !$domain->getId()) {
            $domainExits = $this->domainFactory->create()->load($domain->getVendorId(), "vendor_id");
            $mergedData = array_merge($domainExits->getData(), $data);
            $domainExits->setData($mergedData);
            $domainObject = $domainExits;
        }

        //blend in specific fields from the rule
        try {
            $vendor = $this->vendorFactory->create()->load($domainObject->getVendorId());
            if (!$vendor->getId()) {
                throw \Magento\Framework\Exception\NoSuchEntityException::singleField('vendor_id', $domainObject->getVendorId());
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error occurred when saving coupon: %1', $e->getMessage())
            );
        }

        $this->resourceModel->save($domainObject);

        $config = $this->vendorConfig->create()->getCollection()->addFieldToFilter("vendor_id", $domainObject->getVendorId())
            ->addFieldToFilter("path", self::CONFIG_PATH)->getFirstItem();


        if ($config->getId()) {
            $config->setValue($data["type"])->save();
        } else {
            $this->vendorConfig->create()->setData([
                "vendor_id" => $domainObject->getVendorId(),
                "path" => self::CONFIG_PATH,
                "value" => $data["type"],
                "store" => 0
            ])->save();
        }

        return $domainObject;
    }

    /**
     * Delete domain by Vendor ID.
     *
     * @param int $domainId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($domainId)
    {
        $domainExits = $this->domainFactory->create()->load($domainId);
        $domainExits->delete();
        return true;
    }

}
