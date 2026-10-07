<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config for js/hm-email-check (CL036-TC97): the REST URL and the messages,
 * translated server-side so the deployed js-translation.json is not touched.
 */
class EmailCheckConfig implements ArgumentInterface
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Json $json
    ) {
    }

    public function getConfigJson(): string
    {
        $store = $this->storeManager->getStore();

        return $this->json->serialize([
            'url' => '/rest/' . $store->getCode() . '/V1/hm/email-check',
            'msgUndeliverable' => (string) __('We can\'t deliver email to "%1". Please check the address.', '%1'),
            'msgSuggest' => (string) __('Did you mean %1?', '%1'),
        ]);
    }
}
