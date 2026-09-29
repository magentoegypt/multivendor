<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\ReturnableQty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The website's returnable-quantity formula (Vnecoms\RMA\Model\Request::validateItems).
 */
class ReturnableQtyTest extends TestCase
{
    /**
     * @return array<string, array{string, float, float, float, float, int}>
     */
    public static function cases(): array
    {
        return [
            //                                          status       shipped invoiced refunded in-returns expected
            'complete, shipped == invoiced'        => ['complete',   3.0,    3.0,     0.0,     0.0,       3],
            'complete, partly shipped: invoiced'   => ['complete',   1.0,    3.0,     0.0,     0.0,       3],
            'complete, refunds subtracted'         => ['complete',   3.0,    3.0,     1.0,     0.0,       2],
            'complete, open returns subtracted'    => ['complete',   3.0,    3.0,     0.0,     2.0,       1],
            'complete, refunds and returns'        => ['complete',   4.0,    4.0,     1.0,     2.0,       1],
            'processing: invoiced - returns'       => ['processing', 0.0,    2.0,     0.0,     1.0,       1],
            'processing ignores refunds (website)' => ['processing', 0.0,    2.0,     2.0,     0.0,       2],
            'processing, not invoiced'             => ['processing', 0.0,    0.0,     0.0,     0.0,       0],
            'custom status uses the other branch'  => ['delivered',  3.0,    3.0,     3.0,     0.0,       3],
            'floored at 0'                         => ['complete',   2.0,    2.0,     1.0,     5.0,       0],
            'fraction cut to a whole unit'         => ['complete',   2.5,    2.5,     0.0,     0.0,       2],
            'all returned already'                 => ['complete',   2.0,    2.0,     0.0,     2.0,       0],
            //  A bundle is returned through its child lines, each with its own quantities (bundle.phtml
            //  calls getRmaItem() on the child line): a child line with no shipped quantity of its own
            //  takes the invoiced branch, one shipped separately the shipped branch.
            'bundle child, nothing shipped on it'  => ['complete',   0.0,    2.0,     0.0,     0.0,       2],
            'bundle child, shipped, one returned'  => ['complete',   2.0,    2.0,     0.0,     1.0,       1],
            'bundle child, processing order'       => ['processing', 0.0,    4.0,     0.0,     1.0,       3],
        ];
    }

    #[DataProvider('cases')]
    public function testCalculate(
        string $status,
        float $shipped,
        float $invoiced,
        float $refunded,
        float $inReturns,
        int $expected
    ): void {
        self::assertSame(
            $expected,
            (new ReturnableQty())->calculate($status, $shipped, $invoiced, $refunded, $inReturns)
        );
    }
}
