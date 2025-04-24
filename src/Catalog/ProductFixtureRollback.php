<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @internal Use ProductFixture::rollback() or ProductFixturePool::rollback() instead
 */
class ProductFixtureRollback
{
    private Registry $registry;
    private ProductRepositoryInterface $productRepository;

    public function __construct(Registry $registry, ProductRepositoryInterface $productRepository)
    {
        $this->registry = $registry;
        $this->productRepository = $productRepository;
    }

    public static function create(): ProductFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->get(Registry::class),
            $objectManager->get(ProductRepositoryInterface::class),
        );
    }

    public function execute(ProductFixture ...$productFixtures): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        foreach ($productFixtures as $productFixture) {
            try {
                $this->productRepository->deleteById($productFixture->getSku());
            } catch (\Exception) {
                // this is fine, products has already been removed
            }
        }

        $this->registry->unregister('isSecureArea');
    }
}
