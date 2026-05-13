<?php
/**
* Copyright 2016 vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\VendorsSellerList\Test\Unit\Block;


use Vnecoms\VendorsSellerList\Block\SellerList;

use Magento\Framework\View\Element\Template\Context;

use Magento\Framework\UrlInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;

/**
 * Test for \Vnecoms\VendorsSellerList\Block\SellerList
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SellerListTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SellerList
     */
    private $block;


    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['isAjax']
        );
        $contextMock = $objectManager->getObject(
            Context::class,
            [
                'urlBuilder' => $this->urlBuilderMock,
                'request' => $this->requestMock
            ]
        );

        $this->block = $objectManager->getObject(
            SellerListTest::class,
            [
                'context' => $contextMock,
            ]
        );
    }



}
