<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\ReturnableLines;
use PHPUnit\Framework\TestCase;

/**
 * The lines the website's return form offers (and the admin and seller panels list): a core bundle
 * through its child lines, every other top-level line itself.
 */
class ReturnableLinesTest extends TestCase
{
    /**
     * One order: a simple product, a configurable with its simple child, a core bundle with two
     * selections, and a BundleExtend new_bundle with two selections.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function order(): array
    {
        return self::keyed([
            self::line(10, null, 'simple', 'Mug'),
            self::line(11, null, 'configurable', 'T-shirt'),
            self::line(12, 11, 'simple', 'T-shirt - M'),
            self::line(13, null, 'bundle', 'Gaming Set'),
            self::line(14, 13, 'simple', 'Console'),
            self::line(15, 13, 'simple', 'Controller'),
            self::line(16, null, 'new_bundle', 'Office Pack'),
            self::line(17, 16, 'simple', 'Desk'),
            self::line(18, 16, 'configurable', 'Chair'),
        ]);
    }

    public function testABundleIsOfferedThroughItsChildLinesAndEveryOtherTopLevelLineItself(): void
    {
        self::assertSame([10, 11, 14, 15, 16], array_keys(ReturnableLines::offered(self::order())));
    }

    public function testEachLineIsClassifiedAsTheWebsiteRendersIt(): void
    {
        $lines = self::order();
        $expected = [
            10 => ReturnableLines::OFFERED,
            11 => ReturnableLines::OFFERED,
            12 => ReturnableLines::PART,
            13 => ReturnableLines::BUNDLE,
            14 => ReturnableLines::OFFERED,
            15 => ReturnableLines::OFFERED,
            16 => ReturnableLines::OFFERED,
            17 => ReturnableLines::PART,
            18 => ReturnableLines::PART,
            99 => ReturnableLines::UNKNOWN,
        ];
        foreach ($expected as $itemId => $class) {
            self::assertSame($class, ReturnableLines::classify($lines, $itemId), 'item ' . $itemId);
        }
    }

    public function testNewBundleKeepsItsOwnLineBecauseNoRmaRendererMapsIt(): void
    {
        //  No RMA layout maps new_bundle, so the website renders it with the "default" renderer: the
        //  new_bundle line is the one offered (and the one the panels list), its selections never are.
        $offered = ReturnableLines::offered(self::order());
        self::assertArrayHasKey(16, $offered);
        self::assertArrayNotHasKey(17, $offered);
        self::assertNull(ReturnableLines::bundleOf(self::order(), 17));
    }

    public function testAChildLineKnowsItsBundle(): void
    {
        $bundle = ReturnableLines::bundleOf(self::order(), 15);
        self::assertNotNull($bundle);
        self::assertSame('Gaming Set', $bundle['name']);
        self::assertNull(ReturnableLines::bundleOf(self::order(), 13), 'the bundle line has no bundle');
        self::assertNull(ReturnableLines::bundleOf(self::order(), 12), 'a configurable is not a bundle');
        self::assertNull(ReturnableLines::bundleOf(self::order(), 10));
    }

    public function testAChildLineWhoseParentIsMissingIsNeverOffered(): void
    {
        $lines = self::keyed([self::line(21, 20, 'simple', 'Orphan')]);

        self::assertSame(ReturnableLines::PART, ReturnableLines::classify($lines, 21));
        self::assertSame([], ReturnableLines::offered($lines));
    }

    public function testLinesOfSeveralOrdersKeepTheirOrder(): void
    {
        $lines = self::order() + self::keyed([
            self::line(30, null, 'bundle', 'Kitchen Set'),
            self::line(31, 30, 'simple', 'Pan'),
            self::line(32, null, 'virtual', 'Warranty'),
        ]);

        self::assertSame([10, 11, 14, 15, 16, 31, 32], array_keys(ReturnableLines::offered($lines)));
    }

    public function testStringColumnsFromTheDatabaseAreRead(): void
    {
        //  fetchAll() returns strings; an empty parent_item_id is a top-level line.
        $lines = [
            40 => ['item_id' => '40', 'parent_item_id' => null, 'product_type' => 'bundle', 'name' => 'Set'],
            41 => ['item_id' => '41', 'parent_item_id' => '40', 'product_type' => 'simple', 'name' => 'Part'],
            42 => ['item_id' => '42', 'parent_item_id' => '', 'product_type' => 'simple', 'name' => 'Loose'],
        ];

        self::assertSame([41, 42], array_keys(ReturnableLines::offered($lines)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function line(int $itemId, ?int $parentId, string $type, string $name): array
    {
        return [
            'item_id' => (string) $itemId,
            'parent_item_id' => $parentId === null ? null : (string) $parentId,
            'product_type' => $type,
            'name' => $name,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function keyed(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['item_id']] = $row;
        }

        return $out;
    }
}
