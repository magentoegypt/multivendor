<?php declare(strict_types=1);

namespace Mstore\Wishlist\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Mstore\Wishlist\Api\Data\BuyRequestInterface;

class BuyRequest extends AbstractExtensibleModel implements BuyRequestInterface
{
    /**
     * @inheritDoc
     */
    public function getSuperAttribute()
    {
        return $this->getData(BuyRequestInterface::SUPER_ATTRIBUTE);
    }

    /**
     * @inheritDoc
     */
    public function getQty()
    {
        return $this->getData(BuyRequestInterface::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setSuperAttribute($attribute)
    {
        return $this->setData(BuyRequestInterface::SUPER_ATTRIBUTE, $attribute);
    }

    /**
     * @inheritDoc
     */
    public function setQty($qty)
    {
        return $this->setData(BuyRequestInterface::QTY, $qty);
    }
}
