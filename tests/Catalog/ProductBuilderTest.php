<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ProductBuilderTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private ProductRepositoryInterface $productRepository;
    /**
     * @var ProductFixture[]
     */
    private array $products = [];

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->create(type: ProductRepositoryInterface::class);
        $this->products = [];
    }

    protected function tearDown(): void
    {
        if (!empty($this->products)) {
            foreach ($this->products as $product) {
                ProductFixtureRollback::create()->execute($product);
            }
        }
    }

    public function testDefaultSimpleProduct(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aSimpleProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: Type::TYPE_SIMPLE, actual: $product->getTypeId());
        $this->assertEquals(expected: 'TDD Test Simple Product', actual: $product->getName());
        $this->assertEquals(expected: [1], actual: $product->getWebsiteIds());
        $this->assertEquals(expected: 1, actual: $product->getData('tax_class_id'));
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $this->assertTrue(condition: $stockItem->getIsInStock());
        $this->assertEquals(expected: 100, actual: $stockItem->getQty());
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testSimpleProductWithSpecificAttributes(): void
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(type: StoreManagerInterface::class);
        $secondWebsiteId = $storeManager->getWebsite(websiteId: 'test')->getId();
        $productFixture = new ProductFixture(
            product: ProductBuilder::aSimpleProduct()
                ->withSku(sku: 'foobar')
                ->withName(name: 'Foo Bar')
                ->withStatus(status: Status::STATUS_DISABLED)
                ->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE)
                ->withWebsiteIds(websiteIds: [$secondWebsiteId])
                ->withPrice(price: 9.99)
                ->withTaxClassId(taxClassId: 2)
                ->withIsInStock(inStock: false)
                ->withStockQty(qty: -1)
                ->withWeight(weight: 10)
                ->withBackorders(backorders: 2)
                ->withCustomAttributes(
                    values: [
                        'cost' => 2.0,
                    ],
                )
                ->withImage(fileName: 'image3.png')
                ->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: 'foobar', actual: $product->getSku(), message: 'sku');
        $this->assertEquals(
            expected: 'foobar',
            actual: $product->getUrlKey(),
            message: 'URL key should equal SKU if not set otherwise',
        );
        $this->assertEquals(expected: 'Foo Bar', actual: $product->getName(), message: 'name');
        $this->assertEquals(expected: Status::STATUS_DISABLED, actual: $product->getStatus(), message: 'status');
        $this->assertEquals(
            expected: Visibility::VISIBILITY_NOT_VISIBLE,
            actual: $product->getVisibility(),
            message: 'visibility',
        );
        // current website (1) is always added by ProductRepository
        $this->assertEquals(
            expected: [1, $secondWebsiteId],
            actual: $product->getWebsiteIds(),
            message: 'website ids',
        );
        $this->assertEquals(expected: 9.99, actual: $product->getPrice(), message: 'price');
        $this->assertEquals(expected: 10, actual: $product->getWeight(), message: 'weight');
        $this->assertEquals(expected: 2, actual: $product->getData(key: 'tax_class_id'), message: 'tax class id');
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $this->assertFalse(condition: $stockItem->getIsInStock(), message: 'in stock');
        $this->assertEquals(expected: -1, actual: $stockItem->getQty(), message: 'stock qty');
        $this->assertEquals(expected: 2, actual: $stockItem->getData(key: 'backorders'), message: 'stock backorders');
        $this->assertEquals(
            expected: 2.0,
            actual: $product->getCustomAttribute(attributeCode: 'cost')->getValue(),
            message: 'custom attribute "cost"',
        );
        $this->assertStringMatchesFormat(format: '/i/m/image3%a.png', string: $product->getImage());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/dropdown_attribute.php
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testSimpleProductWithStoreSpecificAttributes(): void
    {
        /*
         * Values from core fixture files
         */
        $secondStoreCode = 'fixture_second_store';
        $userDefinedAttributeCode = 'dropdown_attribute';
        $userDefinedDefaultValue = 1;
        $userDefinedStoreValue = 2;
        // ---

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(type: StoreManagerInterface::class);
        $secondStoreId = (int)$storeManager->getStore(storeId: $secondStoreCode)->getId();
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()
                ->withName(name: 'TDD Test Default Name')
                ->withName(name: 'Store Name', storeId: $secondStoreId)
                ->withStatus(status: Status::STATUS_DISABLED)
                ->withStatus(status: Status::STATUS_ENABLED, storeId: $secondStoreId)
                ->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE)
                ->withVisibility(visibility: Visibility::VISIBILITY_IN_CATALOG, storeId: $secondStoreId)
                ->withCustomAttributes(
                    values: [
                        $userDefinedAttributeCode => $userDefinedDefaultValue,
                    ],
                )
                ->withCustomAttributes(
                    values: [
                        $userDefinedAttributeCode => $userDefinedStoreValue,
                    ],
                    storeId: $secondStoreId,
                )
                ->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: 'TDD Test Default Name',
            actual: $product->getName(),
            message: 'Default name',
        );
        $this->assertEquals(expected: Status::STATUS_DISABLED,
            actual: $product->getStatus(),
            message: 'Default status should be disabled',
        );
        $this->assertEquals(
            expected: Visibility::VISIBILITY_NOT_VISIBLE,
            actual: $product->getVisibility(),
            message: 'Default visibility',
        );
        $this->assertEquals(
            expected: $userDefinedDefaultValue,
            actual: $product->getCustomAttribute($userDefinedAttributeCode)->getValue(),
            message: 'Default custom attribute',
        );

        /** @var Product $product */
        $productInStore = $this->productRepository->getById(
            productId: $productFixture->getId(),
            storeId: $secondStoreId,
        );
        $this->assertEquals(
            expected: 'Store Name',
            actual: $productInStore->getName(),
            message: 'Store specific name',
        );
        $this->assertEquals(
            expected: Status::STATUS_ENABLED,
            actual: $productInStore->getStatus(),
            message: 'Store specific status should be enabled',
        );
        $this->assertEquals(
            expected: Visibility::VISIBILITY_IN_CATALOG,
            actual: $productInStore->getVisibility(),
            message: 'Store specific visibility',
        );
        $this->assertEquals(
            expected: $userDefinedStoreValue,
            actual: $productInStore->getCustomAttribute($userDefinedAttributeCode)->getValue(),
            message: 'Store specific custom attribute',
        );
    }

    public function testRandomSkuOnBuild(): void
    {
        $builder = ProductBuilder::aSimpleProduct();
        $productFixture = new ProductFixture(
            product: $builder->build(),
        );
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $productFixture->getSku());
        $this->products[] = $productFixture;

        $otherProductFixture = new ProductFixture(
            product: $builder->build(),
        );
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $otherProductFixture->getSku());
        $this->assertNotEquals(expected: $productFixture->getSku(), actual: $otherProductFixture->getSku());
        $this->products[] = $otherProductFixture;
    }

    public function testRandomSkuOnBuildWithoutSave(): void
    {
        $product = ProductBuilder::aSimpleProduct()->buildWithoutSave();
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $product->getSku());

        $otherProduct = ProductBuilder::aSimpleProduct()->buildWithoutSave();
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $otherProduct->getSku());
        $this->assertNotEquals(expected: $product->getSku(), actual: $otherProduct->getSku());
    }

    public function testProductCanBeLoadedWithCollection(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aSimpleProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(type: SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(field: 'sku', value: $productFixture->getSku());
        $productsFromCollection = $this->productRepository->getList(
            searchCriteria: $searchCriteriaBuilder->create(),
        )->getItems();
        $this->assertCount(
            expectedCount: 1,
            haystack: $productsFromCollection,
            message: 'The product should be able to be loaded from collection',
        );
    }

    public function testDefaultVirtualProduct(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aVirtualProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: Type::TYPE_VIRTUAL, actual: $product->getTypeId());
        $this->assertEquals(expected: 'TDD Test Virtual Product', actual: $product->getName());
        $this->assertEquals(expected: [1], actual: $product->getWebsiteIds());
        $this->assertTrue(
            condition: $product->getExtensionAttributes()->getStockItem()->getIsInStock(),
        );
    }

    public function testDefaultDownloadableProduct(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aDownloadableProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: DownloadableType::TYPE_DOWNLOADABLE, actual: $product->getTypeId());
        $this->assertEquals(expected: 'TDD Test Downloadable Product', actual: $product->getName());
        $this->assertEquals(expected: [1], actual: $product->getWebsiteIds());

        $extensionAttributes = $product->getExtensionAttributes();
        $this->assertTrue(
            condition: $extensionAttributes->getStockItem()->getIsInStock(),
        );

        $productLinks = $extensionAttributes->getDownloadableProductLinks();
        $this->assertCount(expectedCount: 1, haystack: $productLinks);
        $productLink = array_shift(array: $productLinks);
        $this->assertSame(expected: 'https://magento.test/', actual: $productLink->getLinkUrl());
        $this->assertSame(expected: 'Downloadable Item', actual: $productLink->getTitle());
        $this->assertEquals(expected: 54.99, actual: $productLink->getPrice());
    }
}
