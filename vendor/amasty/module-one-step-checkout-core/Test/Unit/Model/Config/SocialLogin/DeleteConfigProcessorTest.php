<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Config\SocialLogin;

use Amasty\CheckoutCore\Model\Config\SocialLogin\DeleteConfigProcessor;
use Magento\Config\Model\Config\Loader;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see DeleteConfigProcessor
 */
class DeleteConfigProcessorTest extends TestCase
{
    private const SCOPE = 'stores';
    private const SCOPE_ID  = 1;
    
    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @var Loader|MockObject
     */
    private $loaderMock;

    /**
     * @var ReinitableConfigInterface|MockObject
     */
    private $reinitableConfigMock;

    /**
     * @var DeleteConfigProcessor
     */
    private $subject;

    protected function setUp(): void
    {
        $this->writerMock = $this->createMock(WriterInterface::class);
        $this->loaderMock = $this->createMock(Loader::class);
        $this->reinitableConfigMock = $this->createMock(ReinitableConfigInterface::class);

        $this->subject = new DeleteConfigProcessor($this->writerMock, $this->loaderMock, $this->reinitableConfigMock);
    }

    /**
     * @return void
     *
     * @covers DeleteConfigProcessor::process
     */
    public function testProcessWithValue(): void
    {
        $this->loaderMock->expects($this->once())->method('getConfigByPath')->willReturn(
            ['amsociallogin/general/enabled' => '1', 'amsociallogin/general/login_position' => 'popup,above_login']
        );
        $this->writerMock->expects($this->never())->method('delete');
        $this->reinitableConfigMock->expects($this->never())->method('reinit');
        $this->subject->process(self::SCOPE, self::SCOPE_ID);
    }

    /**
     * @return void
     *
     * @covers DeleteConfigProcessor::process
     */
    public function testProcessWithoutValue(): void
    {
        $this->loaderMock->expects($this->once())->method('getConfigByPath')->willReturn(
            ['amsociallogin/general/enabled' => '1']
        );
        $this->writerMock->expects($this->once())->method('delete');
        $this->reinitableConfigMock->expects($this->once())->method('reinit');
        $this->subject->process(self::SCOPE, self::SCOPE_ID);
    }
}
