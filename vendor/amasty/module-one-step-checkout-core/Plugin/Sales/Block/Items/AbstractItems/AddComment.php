<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Sales\Block\Items\AbstractItems;

use Amasty\CheckoutCore\Model\Config;
use Magento\Sales\Block\Items\AbstractItems;

class AddComment
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    public function __construct(
        Config $checkoutConfig
    ) {
        $this->checkoutConfig = $checkoutConfig;
    }

    /**
     * @param AbstractItems $subject
     * @param string $result
     *
     * @return string
     */
    public function afterToHtml(
        AbstractItems $subject,
        $result
    ) {
        if (!$this->checkoutConfig->isEnabled()) {
            return $result;
        }
        foreach ($subject->getLayout()->getUpdate()->getHandles() as $handle) {
            if (substr($handle, 0, 12) !== 'sales_email_') {
                return $result;
            }
            /** @var  \Magento\Sales\Model\Order $order */
            $order = $subject->getOrder();
            if (!$order || !$order->getId()) {
                return $result;
            }

            if ($this->checkoutConfig->getAdditionalOptions('comment')) {
                $commentsBlock = $subject->getLayout()
                    ->createBlock(
                        \Amasty\CheckoutCore\Block\Sales\Order\Email\Comments::class,
                        'amcheckout.comments',
                        [
                            'data' => [
                                'order_entity' => $order
                            ]
                        ]
                    );

                $result = $commentsBlock->toHtml() . $result;
            }
        }

        return $result;
    }
}
