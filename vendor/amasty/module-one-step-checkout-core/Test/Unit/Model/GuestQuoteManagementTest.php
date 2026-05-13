<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model;

use Amasty\CheckoutCore\Model\GuestQuoteManagement;
use Amasty\CheckoutCore\Test\Unit\Traits;

/**
 * @see GuestQuoteManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class GuestQuoteManagementTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    private const QUOTE_ID = 1;

    /**
     * @covers GuestQuoteManagement::saveInsertedInfo
     */
    public function testSaveInsertedInfo()
    {
        $cartId = 1;
        $guestQuoteManagement = $this->createPartialMock(GuestQuoteManagement::class, []);

        $quoteMaskId = $this->createPartialMock(
            \Magento\Quote\Model\QuoteIdMask::class,
            ['load']
        );
        $quoteMaskId->expects($this->any())->method('load')->with($cartId, 'masked_id')
            ->willReturn($quoteMaskId);
        $quoteMaskId->setQuoteId(self::QUOTE_ID);

        $quoteIdMaskFactory = $this->createPartialMock(
            \Magento\Quote\Model\QuoteIdMaskFactory::class,
            ['create']
        );
        $quoteIdMaskFactory->expects($this->any())->method('create')
            ->willReturn($quoteMaskId);

        $quoteManagement = $this->createMock(\Amasty\CheckoutCore\Model\QuoteManagement::class);
        $quoteManagement->expects($this->any())->method('saveInsertedInfo')
            ->with(self::QUOTE_ID)
            ->willReturn(true);

        $this->setProperty(
            $guestQuoteManagement,
            'quoteIdMaskFactory',
            $quoteIdMaskFactory,
            GuestQuoteManagement::class
        );
        $this->setProperty(
            $guestQuoteManagement,
            'quoteManagement',
            $quoteManagement,
            GuestQuoteManagement::class
        );

        $this->assertTrue($guestQuoteManagement->saveInsertedInfo($cartId));
    }
}
