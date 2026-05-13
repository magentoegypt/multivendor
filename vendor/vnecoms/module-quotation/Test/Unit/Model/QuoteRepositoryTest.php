<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Test\Unit\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Vnecoms\Quotation\Model\QuoteRepository;
use Vnecoms\Quotation\Model\Quote;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteRepositoryTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->storeManagerMock = $this->getMock('\Magento\Store\Model\StoreManagerInterface');

        $this->model = $objectManager->getObject(
            'Vnecoms\Quotation\Model\QuoteRepository',
            [
                'quoteFactory' => $this->quoteFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'searchResultsDataFactory' => $this->searchResultsDataFactory,
                'quoteCollection' => $this->quoteCollectionMock,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessorMock
            ]
        );
    }
}