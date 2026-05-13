<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\Data\QuotePasswordsInterface;
use Magento\Framework\Model\AbstractModel;

class QuotePasswords extends AbstractModel implements QuotePasswordsInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\CheckoutCore\Model\ResourceModel\QuotePasswords::class);
    }

    /**
     * @inheritdoc
     */
    public function getQuoteId()
    {
        return $this->_getData(QuotePasswordsInterface::QUOTE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQuoteId($quoteId)
    {
        $this->setData(QuotePasswordsInterface::QUOTE_ID, $quoteId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPasswordHash()
    {
        return $this->_getData(QuotePasswordsInterface::PASSWORD_HASH);
    }

    /**
     * @inheritdoc
     */
    public function setPasswordHash($passwordHash)
    {
        $this->setData(QuotePasswordsInterface::PASSWORD_HASH, $passwordHash);

        return $this;
    }
}
