<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Adminhtml\Reports;

use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class RefreshButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getButtonData()
    {
        $url = $this->context->getUrl('amasty_checkout/reports/index');

        return [
            'label' => __('Refresh'),
            'class' => 'refresh primary',
            'on_click' => "submitRefresh('$url')",
        ];
    }
}
