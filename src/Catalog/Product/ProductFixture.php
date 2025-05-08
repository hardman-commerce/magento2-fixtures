<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;

class ProductFixture
{
    private ProductInterface $product;

    public function __construct(ProductInterface $product)
    {
        $this->product = $product;
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function getId(): int
    {
        return (int)$this->product->getId();
    }

    public function getSku(): string
    {
        return $this->product->getSku();
    }

    public function rollback(): void
    {
        ProductFixtureRollback::create()->execute($this);
    }
}
