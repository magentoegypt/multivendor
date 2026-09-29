<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MagentoEgypt\HubAppReturns\Model\Rma\Paging;
use PHPUnit\Framework\TestCase;

class PagingTest extends TestCase
{
    public function testDefaultsAndPageInfo(): void
    {
        $paging = Paging::fromArgs([], 10, 20);
        self::assertSame(10, $paging->pageSize);
        self::assertSame(1, $paging->currentPage);
        self::assertSame(0, $paging->offset());
        self::assertSame(['page_size' => 10, 'current_page' => 1, 'total_pages' => 3], $paging->pageInfo(21));
        self::assertSame(['page_size' => 10, 'current_page' => 1, 'total_pages' => 0], $paging->pageInfo(0));
    }

    public function testOffset(): void
    {
        self::assertSame(40, Paging::fromArgs(['pageSize' => 20, 'currentPage' => 3], 10, 50)->offset());
    }

    public function testPageSizeAboveTheDocumentedMaximumIsRefused(): void
    {
        $this->expectException(GraphQlInputException::class);
        Paging::fromArgs(['pageSize' => 21], 10, 20);
    }

    public function testPageZeroIsRefused(): void
    {
        $this->expectException(GraphQlInputException::class);
        Paging::fromArgs(['currentPage' => 0], 10, 20);
    }
}
