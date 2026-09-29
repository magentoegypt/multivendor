<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Seller;

use MagentoEgypt\HubApp\Model\Seller\SellerName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The seller-name rule of the core module (HubApp\Model\Seller, owned by HubAppVendors' author).
 *
 * @covers \MagentoEgypt\HubApp\Model\Seller\SellerName
 */
class SellerNameTest extends TestCase
{
    /**
     * @return array<string, array{string|null, string|null, string}>
     */
    public static function sourceCases(): array
    {
        return [
            'company wins' => ['TechGear Pro', 'techgear', 'TechGear Pro'],
            'company is trimmed' => ['  Loly Store ', 'loly', 'Loly Store'],
            'arabic company' => ['ابل', 'apple_store', 'ابل'],
            'empty company -> humanised code' => ['', 'test_1', 'Test 1'],
            'null company -> humanised code' => [null, 'magento_tester', 'Magento Tester'],
            '"0" is not a name (V3S2, V8S2)' => ['0', 'V8S2', 'V8S2'],
            '"." is not a name (V2S2)' => ['.', 'V2S2', 'V2S2'],
            'seller casing is kept' => ['', 'MIA', 'MIA'],
            'mixed separators' => ['', 'hub-market.seller_two', 'Hub Market Seller Two'],
            'digits other than 0 are a name' => ['7', 'code', '7'],
        ];
    }

    #[DataProvider('sourceCases')]
    public function testSource(?string $company, ?string $code, string $expected): void
    {
        $this->assertSame($expected, SellerName::source($company, $code));
    }

    public function testIsName(): void
    {
        $this->assertTrue(SellerName::isName('Loly'));
        $this->assertTrue(SellerName::isName('ابل'));
        $this->assertTrue(SellerName::isName('24/7'));
        $this->assertFalse(SellerName::isName(''));
        $this->assertFalse(SellerName::isName(null));
        $this->assertFalse(SellerName::isName('0'));
        $this->assertFalse(SellerName::isName(' . '));
    }

    public function testMarketplaceIsLatin(): void
    {
        $this->assertSame('Hub Market', SellerName::MARKETPLACE);
    }
}
