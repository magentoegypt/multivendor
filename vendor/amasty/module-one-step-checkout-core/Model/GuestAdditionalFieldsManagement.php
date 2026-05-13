<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\GuestAdditionalFieldsManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestAdditionalFieldsManagement implements GuestAdditionalFieldsManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var AdditionalFieldsManagement
     */
    private $fieldsManagement;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        AdditionalFieldsManagement $fieldsManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->fieldsManagement = $fieldsManagement;
    }

    /**
     * @inheritdoc
     */
    public function save($cartId, $comment)
    {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->fieldsManagement->save(
            $quoteIdMask->getQuoteId(),
            $comment
        );
    }
}
