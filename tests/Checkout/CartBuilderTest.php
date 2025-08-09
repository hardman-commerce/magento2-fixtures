<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Product\ProductBuilder;
use TddWizard\Fixtures\Catalog\Product\ProductFixture;

class CartBuilderTest extends TestCase
{
    private ProductFixture $productFixture;
    private readonly ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()->build(),
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
        $cartBuilder = CartBuilder::forCurrentSession();
        $cartBuilder = $cartBuilder->withProductRequest(
            sku: $this->productFixture->getSku(),
            qty: $qty,
            request: ['options' => [$customOptionId => $customOptionValue]],
        );
        $cart = $cartBuilder->build();
        $quoteItems = $cart->getAllItems();
        $this->assertCount(expectedCount: 1, haystack: $quoteItems, message: "1 quote item should be added");
        /** @var Item $quoteItem */
        $quoteItem = reset(array: $quoteItems);
        $serializedBuyRequest = $quoteItem->getOptionByCode(code: 'info_buyRequest')->getValue();
        $serializer = $this->objectManager->get(Json::class);
        $this->assertJsonStringEqualsJsonString(
            $serializer->serialize(data: ['qty' => $qty, 'options' => ['42' => 'foobar']]),
            $serializedBuyRequest,
            "Value of info_buyRequest option should be as configured",
        );
    }
}
