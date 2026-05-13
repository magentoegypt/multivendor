<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Component;

use Magento\Ui\Component\Form as UiFrom;

class Form extends UiFrom
{
    /**
     * {@inheritdoc}
     */
    public function getDataSourceData()
    {
        return $this->getContext()->getDataProvider()->getData();
    }
}
