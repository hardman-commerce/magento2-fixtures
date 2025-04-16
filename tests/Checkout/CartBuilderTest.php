<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Catalog\ProductFixture;

class CartBuilderTest extends TestCase
{
    private ProductFixture $productFixture;

    protected function setUp(): void
    {
        $this->productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()->build()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testProductCanBeAddedWithCustomBuyRequest(): void
    {
        $qty = 2;
        $customOptionId = 42;
        $customOptionValue = 'foobar';
        $cart = CartBuilder::forCurrentSession()->withProductRequest(
            $this->productFixture->getSku(),
            $qty,
            ['options' => [$customOptionId => $customOptionValue]]
        )->build();
        $quoteItems = $cart->getQuote()->getAllItems();
        $this->assertCount(1, $quoteItems, "1 quote item should be added");
        /** @var Item $quoteItem */
        $quoteItem = reset($quoteItems);
        $serializedBuyRequest = $quoteItem->getOptionByCode('info_buyRequest')->getValue();
        if (!json_decode($serializedBuyRequest)) {
            // Magento 2.1 PHP serialization
            $serializedBuyRequest = json_encode(unserialize($serializedBuyRequest, []));
        }
        $this->assertJsonStringEqualsJsonString(
            json_encode(['qty' => $qty, 'options' => ['42' => 'foobar']]),
            $serializedBuyRequest,
            "Value of info_buyRequest option should be as configured"
        );
    }
}
