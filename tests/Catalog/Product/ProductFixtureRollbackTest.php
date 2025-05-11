<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ProductFixtureRollbackTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
    }

    public function testRollbackSingleProductFixture(): void
    {
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()->build(),
        );
        ProductFixtureRollback::create()->execute($productFixture);
        $this->expectException(NoSuchEntityException::class);
        $this->productRepository->getById($productFixture->getId());
    }

    public function testRollbackMultipleProductFixtures(): void
    {
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()->build(),
        );
        $otherProductFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()->build(),
        );
        ProductFixtureRollback::create()->execute($productFixture, $otherProductFixture);
        $productDeleted = false;
        try {
            $this->productRepository->getById($productFixture->getId());
        } catch (NoSuchEntityException $e) {
            $productDeleted = true;
        }
        $this->assertTrue($productDeleted, 'First product should be deleted');
        $this->expectException(NoSuchEntityException::class);
        $this->productRepository->getById($otherProductFixture->getId());
    }
}
