<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Block\Address\Edit;

use Amasty\CheckoutCore\Block\Frontend\Customer\Address\Edit\CustomFields;
use Amasty\CheckoutCore\Model\CustomField\AddressStorage;
use Magento\Customer\Block\Address\Edit;
use Magento\Framework\App\ProductMetadataInterface;

class AddCustomFields
{
    private const ZIP_PATTERN = '/(<div class="field zip required">.+<\/script>\s+<\/div>\s+<\/div>)/s';

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var AddressStorage
     */
    private $addressStorage;

    public function __construct(
        AddressStorage $addressStorage,
        ProductMetadataInterface $productMetadata
    ) {
        $this->addressStorage = $addressStorage;
        $this->productMetadata = $productMetadata;
    }

    public function afterToHtml(Edit $subject, string $result): string
    {
        if ($this->productMetadata->getEdition() === 'Enterprise') {
            return $result;
        }

        if ($subject->getAddress()) {
            $this->addressStorage->setAddress($subject->getAddress());
        }

        $customFieldsHtml = $subject->getLayout()
            ->createBlock(CustomFields::class, 'amcheckout_custom_fields')
            ->toHtml();

        if (preg_match(self::ZIP_PATTERN, $result, $matches)) {
            $result = str_replace($matches[0], $matches[0] . $customFieldsHtml, $result);
        }

        return $result;
    }
}
