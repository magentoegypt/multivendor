<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Config;

use Amasty\CheckoutCore\Model\Config\SocialLoginProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Amasty\CheckoutCore\Model\Config\SocialLogin\CheckoutPositionValue;
use Magento\Framework\App\Config\ReinitableConfigInterface;

/**
 * @see SocialLoginProcessor
 */
class SocialLoginProcessorTest extends TestCase
{
    private const SCOPE = 'default';
    private const SCOPE_ID  = 0;
    private const OSC_CONFIG_CHANGED = 'admin_system_config_changed_section_amasty_checkout';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @var ReinitableConfigInterface|MockObject
     */
    private $reinitableConfigMock;

    /**
     * @var CheckoutPositionValue|MockObject
     */
    private $checkoutPositionValueMock;

    /**
     * @var SocialLoginProcessor
     */
    private $subject;
    
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->writerMock = $this->createMock(WriterInterface::class);
        $this->reinitableConfigMock = $this->createMock(ReinitableConfigInterface::class);
        $this->checkoutPositionValueMock = $this->createMock(CheckoutPositionValue::class);

        $this->subject = new SocialLoginProcessor(
            $this->scopeConfigMock,
            $this->writerMock,
            $this->reinitableConfigMock,
            $this->checkoutPositionValueMock
        );
    }

    /**
     * @return void
     *
     * @covers SocialLoginProcessor::process
     */
    public function testProcessWithEqualValues(): void
    {
        $this->checkoutPositionValueMock->expects($this->once())->method('getPositionValue')->willReturn(1);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn(1);
        $this->writerMock->expects($this->never())->method('save');
        $this->reinitableConfigMock->expects($this->never())->method('reinit');
        $this->subject->process(self::SCOPE, self::SCOPE_ID, self::OSC_CONFIG_CHANGED);
    }

    /**
     * @return void
     *
     * @covers SocialLoginProcessor::process
     */
    public function testProcessWithDifferentValues(): void
    {
        $this->checkoutPositionValueMock->expects($this->once())->method('getPositionValue')->willReturn(0);
        $this->scopeConfigMock->expects($this->atLeastOnce())->method('getValue')->willReturn(1);
        $this->writerMock->expects($this->once())->method('save');
        $this->reinitableConfigMock->expects($this->once())->method('reinit');
        $this->subject->process(self::SCOPE, self::SCOPE_ID, self::OSC_CONFIG_CHANGED);
    }
}
