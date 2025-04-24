<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
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
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
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
            ProductBuilder::aSimpleProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById($productFixture->getId());
        $this->assertEquals(Type::TYPE_SIMPLE, $product->getTypeId());
        $this->assertEquals('TDD Test Simple Product', $product->getName());
        $this->assertEquals([1], $product->getWebsiteIds());
        $this->assertEquals(1, $product->getData('tax_class_id'));
        $this->assertTrue(
            $product->getExtensionAttributes()->getStockItem()->getIsInStock(),
        );
        $this->assertEquals(100, $product->getExtensionAttributes()->getStockItem()->getQty());
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testSimpleProductWithSpecificAttributes(): void
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $secondWebsiteId = $storeManager->getWebsite('test')->getId();
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()
                ->withSku('foobar')
                ->withName('Foo Bar')
                ->withStatus(Status::STATUS_DISABLED)
                ->withVisibility(Product\Visibility::VISIBILITY_NOT_VISIBLE)
                ->withWebsiteIds([$secondWebsiteId])
                ->withPrice(9.99)
                ->withTaxClassId(2)
                ->withIsInStock(false)
                ->withStockQty(-1)
                ->withWeight(10)
                ->withBackorders(2)
                ->withCustomAttributes(
                    [
                        'cost' => 2.0,
                    ],
                )
                ->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById($productFixture->getId());
        $this->assertEquals('foobar', $product->getSku(), 'sku');
        $this->assertEquals('foobar', $product->getUrlKey(), 'URL key should equal SKU if not set otherwise');
        $this->assertEquals('Foo Bar', $product->getName(), 'name');
        $this->assertEquals(Status::STATUS_DISABLED, $product->getStatus(), 'status');
        $this->assertEquals(Product\Visibility::VISIBILITY_NOT_VISIBLE, $product->getVisibility(), 'visibility');
        // current website (1) is always added by ProductRepository
        $this->assertEquals([1, $secondWebsiteId], $product->getWebsiteIds(), 'website ids');
        $this->assertEquals(9.99, $product->getPrice(), 'price');
        $this->assertEquals(10, $product->getWeight(), 'weight');
        $this->assertEquals(2, $product->getData('tax_class_id'), 'tax class id');
        $this->assertFalse(
            $product->getExtensionAttributes()->getStockItem()->getIsInStock(),
            'in stock',
        );
        $this->assertEquals(-1, $product->getExtensionAttributes()->getStockItem()->getQty(), 'stock qty');
        $this->assertEquals(
            2,
            $product->getExtensionAttributes()->getStockItem()->getData('backorders'),
            'stock backorders',
        );
        $this->assertEquals(2.0, $product->getCustomAttribute('cost')->getValue(), 'custom attribute "cost"');
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
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $secondStoreId = (int)$storeManager->getStore($secondStoreCode)->getId();
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()
                ->withName('TDD Test Default Name')
                ->withName('Store Name', $secondStoreId)
                ->withStatus(Status::STATUS_DISABLED)
                ->withStatus(Status::STATUS_ENABLED, $secondStoreId)
                ->withVisibility(Product\Visibility::VISIBILITY_NOT_VISIBLE)
                ->withVisibility(Product\Visibility::VISIBILITY_IN_CATALOG, $secondStoreId)
                ->withCustomAttributes(
                    [
                        $userDefinedAttributeCode => $userDefinedDefaultValue,
                    ],
                )
                ->withCustomAttributes(
                    [
                        $userDefinedAttributeCode => $userDefinedStoreValue,
                    ],
                    $secondStoreId,
                )
                ->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById($productFixture->getId());
        $this->assertEquals('TDD Test Default Name', $product->getName(), 'Default name');
        $this->assertEquals(Status::STATUS_DISABLED, $product->getStatus(), 'Default status should be disabled');
        $this->assertEquals(
            Product\Visibility::VISIBILITY_NOT_VISIBLE,
            $product->getVisibility(),
            'Default visibility',
        );
        $this->assertEquals(
            $userDefinedDefaultValue,
            $product->getCustomAttribute($userDefinedAttributeCode)->getValue(),
            'Default custom attribute',
        );

        /** @var Product $product */
        $productInStore = $this->productRepository->getById($productFixture->getId(), false, $secondStoreId);
        $this->assertEquals('Store Name', $productInStore->getName(), 'Store specific name');
        $this->assertEquals(
            Status::STATUS_ENABLED,
            $productInStore->getStatus(),
            'Store specific status should be enabled',
        );
        $this->assertEquals(
            Product\Visibility::VISIBILITY_IN_CATALOG,
            $productInStore->getVisibility(),
            'Store specific visibility',
        );
        $this->assertEquals(
            $userDefinedStoreValue,
            $productInStore->getCustomAttribute($userDefinedAttributeCode)->getValue(),
            'Store specific custom attribute',
        );
    }

    public function testRandomSkuOnBuild(): void
    {
        $builder = ProductBuilder::aSimpleProduct();
        $productFixture = new ProductFixture(
            $builder->build(),
        );
        $this->assertMatchesRegularExpression('/[0-9a-f]{32}/', $productFixture->getSku());
        $this->products[] = $productFixture;

        $otherProductFixture = new ProductFixture(
            $builder->build(),
        );
        $this->assertMatchesRegularExpression('/[0-9a-f]{32}/', $otherProductFixture->getSku());
        $this->assertNotEquals($productFixture->getSku(), $otherProductFixture->getSku());
        $this->products[] = $otherProductFixture;
    }

    public function testRandomSkuOnBuildWithoutSave(): void
    {
        $product = ProductBuilder::aSimpleProduct()->buildWithoutSave();
        $this->assertMatchesRegularExpression('/[0-9a-f]{32}/', $product->getSku());

        $otherProduct = ProductBuilder::aSimpleProduct()->buildWithoutSave();
        $this->assertMatchesRegularExpression('/[0-9a-f]{32}/', $otherProduct->getSku());
        $this->assertNotEquals($product->getSku(), $otherProduct->getSku());
    }

    public function testProductCanBeLoadedWithCollection(): void
    {
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter('sku', $productFixture->getSku());
        $productsFromCollection = $this->productRepository->getList($searchCriteriaBuilder->create())->getItems();
        $this->assertCount(
            1,
            $productsFromCollection,
            'The product should be able to be loaded from collection',
        );
    }

    public function testDefaultVirtualProduct(): void
    {
        $productFixture = new ProductFixture(
            ProductBuilder::aVirtualProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById($productFixture->getId());
        $this->assertEquals(Type::TYPE_VIRTUAL, $product->getTypeId());
        $this->assertEquals('TDD Test Virtual Product', $product->getName());
        $this->assertEquals([1], $product->getWebsiteIds());
        $this->assertTrue(
            $product->getExtensionAttributes()->getStockItem()->getIsInStock(),
        );
    }

    public function testDefaultDownloadableProduct(): void
    {
        $productFixture = new ProductFixture(
            ProductBuilder::aDownloadableProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById($productFixture->getId());
        $this->assertEquals(DownloadableType::TYPE_DOWNLOADABLE, $product->getTypeId());
        $this->assertEquals('TDD Test Downloadable Product', $product->getName());
        $this->assertEquals([1], $product->getWebsiteIds());

        $extensionAttributes = $product->getExtensionAttributes();
        $this->assertTrue(
            $extensionAttributes->getStockItem()->getIsInStock(),
        );

        $productLinks = $extensionAttributes->getDownloadableProductLinks();
        $this->assertCount(1, $productLinks);
        $productLink = array_shift($productLinks);
        $this->assertSame('https://magento.test/', $productLink->getLinkUrl());
        $this->assertSame('Downloadable Item', $productLink->getTitle());
        $this->assertEquals(54.99, $productLink->getPrice());
    }
}
