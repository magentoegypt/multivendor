<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Block\Onepage\LayoutProcessor;

use Amasty\Checkout\Model\BillingAddress;
use Amasty\Checkout\Model\PlaceholderRepository;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalker;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalkerFactory;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\ModuleEnable;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class PlaceholdersProcessor implements LayoutProcessorInterface
{
    public const CUSTOM_FIELDS = ['custom_field_1', 'custom_field_2', 'custom_field_3'];

    public const CUSTOMER_INFORMATION_PATH = '{SHIPPING_ADDRESS_FIELDSET}.>>';

    public const ATTRIBUTE_PATHS = [
        '{PAYMENT}.>>.beforeMethods.>>.order-attributes-fields.>>',
        '{SHIPPING_ADDRESS}.>>.before-shipping-method-form.>>.order-attributes-fields.>>',
        '{SIDEBAR}.>>.summary.>>.totals.>>.order-attributes-fields.>>'
    ];

    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var LayoutWalkerFactory
     */
    private $walkerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    /**
     * @var PlaceholderRepository
     */
    private $placeholderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LayoutWalker
     */
    private $walker;

    /**
     * @var BillingAddress
     */
    private $billingAddress;

    public function __construct(
        Config $checkoutConfig,
        LayoutWalkerFactory $walkerFactory,
        StoreManagerInterface $storeManager,
        ModuleEnable $moduleEnable,
        BillingAddress $billingAddress,
        PlaceholderRepository $placeholderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->checkoutConfig = $checkoutConfig;
        $this->walkerFactory = $walkerFactory;
        $this->storeManager = $storeManager;
        $this->moduleEnable = $moduleEnable;
        $this->billingAddress = $billingAddress;
        $this->placeholderRepository = $placeholderRepository;
        $this->searchCriteriaBuilder =  $searchCriteriaBuilder;
    }

    /**
     * @param array $jsLayout
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function process($jsLayout): array
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $jsLayout;
        }

        $this->walker = $this->walkerFactory->create(['layoutArray' => $jsLayout]);

        $fields = $this->getFields();

        $this->setPlaceholder([self::CUSTOMER_INFORMATION_PATH], $fields);
        $this->setPlaceholder($this->billingAddress->getBillingPath($this->walker), $fields);

        if ($this->moduleEnable->isOrderAttributesEnable()) {
            $this->setPlaceholder(self::ATTRIBUTE_PATHS, $fields);
        }

        return $this->walker->getResult();
    }

    /**
     * @param array $paths
     * @param array $fields
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function setPlaceholder(array $paths, array $fields): void
    {
        foreach ($paths as $path) {
            if ($layoutRoot = $this->walker->getValue($path)) {
                foreach ($layoutRoot as $key => $value) {
                    if (array_key_exists($key, $fields)) {
                        $keyPath = str_replace('.', LayoutWalker::ESCAPED_SEPARATOR, $key);
                        if ($key == 'street') {
                            $this->walker->setValue($path . '.' . $keyPath . '.>>.0.config.placeholder', $fields[$key]);
                        } else {
                            $this->walker->setValue($path . '.' . $keyPath . '.config.placeholder', $fields[$key]);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    private function getFields(): array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $this->searchCriteriaBuilder->addFilter('store_id', $storeId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $items = $this->placeholderRepository->getList($searchCriteria);
        $fields = [];
        foreach ($items as $item) {
            if (in_array($item['attribute_code'], self::CUSTOM_FIELDS)) {
                $fields['custom_attributes.' . $item['attribute_code']] = $item['placeholder'];
            } else {
                $fields[$item['attribute_code']] = $item['placeholder'];
            }
        }

        return $fields;
    }
}
