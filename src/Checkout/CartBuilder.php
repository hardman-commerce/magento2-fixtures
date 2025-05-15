<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Attribute;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use TddWizard\Fixtures\Checkout\CartBuilder as TddCartBuilder;

class CartBuilder
{
    /**
     * @var DataObject[][] Array in the form [sku => [buyRequest]] (multiple requests per sku are possible)
     */
    private array $addToCartRequests;

    final public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Cart $cart,
    ) {
        $this->addToCartRequests = [];
    }

    public static function forCurrentSession(): CartBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            productRepository: $objectManager->create(type: ProductRepositoryInterface::class),
            cart: $objectManager->create(type: Cart::class),
        );
    }

    public function withSimpleProduct(string $sku, float $qty = 1): CartBuilder
    {
        $result = clone $this;
        $result->addToCartRequests[$sku][] = new DataObject(data: ['qty' => $qty]);

        return $result;
    }

    public function withConfigurableProduct(
        string $sku,
        array $options,
        float $qty = 1.0,
    ): TddCartBuilder {
        return $this->withProductRequest(
            sku: $sku,
            qty: $qty,
            request: [
                'options' => $options,
            ],
        );
    }

    public function withGroupedProduct(
        string $sku,
        array $options,
        float $qty = 1.0,
    ): TddCartBuilder {
        return $this->withProductRequest(
            sku: $sku,
            qty: $qty,
            request: [
                'options' => $options,
            ],
        );
    }

    public function withReservedOrderId(string $orderId): CartBuilder
    {
        $result = clone $this;
        $result->cart->getQuote()->setReservedOrderId(reservedOrderId: $orderId);

        return $result;
    }

    /**
     * Lower-level API to support arbitrary products
     *
     * @param mixed[] $request
     */
    public function withProductRequest(string $sku, float|int $qty = 1, array $request = []): CartBuilder
    {
        $result = clone $this;
        $requestInfo = array_merge(['qty' => $qty], $request);
        $result->addToCartRequests[$sku][] = new DataObject(data: $requestInfo);

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    public function build(): Cart
    {
        foreach ($this->addToCartRequests as $sku => $requests) {
            /** @var Product $product */
            $product = $this->productRepository->get(sku: $sku);

            // @todo Remove and resolve stock issues with configurables
            ObjectManager::getInstance()
                ->get(type: \Magento\Catalog\Helper\Product::class)
                ->setSkipSaleableCheck(skipSaleableCheck: true);

            foreach ($requests as $requestInfo) {
                switch ($product->getTypeId()) {
                    case Grouped::TYPE_CODE:
                        $requestOptions = $requestInfo->getData(key: 'options') ?: [];
                        $requestInfo->unsetData(key: 'options');

                        /** @var \Magento\GroupedProduct\Model\Product\Type\Grouped $typeInstance */
                        $typeInstance = $product->getTypeInstance();
                        /** @var ProductInterface[] $associatedProducts */
                        $associatedProducts = $typeInstance->getAssociatedProducts(product: $product);

                        $requestInfo->setData(key: 'product', value: $product->getId());
                        $requestInfo->setData(key: 'item', value: $product->getId());
                        // @todo Replace with child id => qty
                        $superGroup = [];
                        foreach ($requestOptions as $associatedSku => $qtyOrdered) {
                            /** @var ProductInterface $childProduct */
                            $childProduct = current(
                                array_filter(
                                    array: $associatedProducts,
                                    callback: static fn (ProductInterface $associatedProduct): bool => (
                                        $associatedSku === $associatedProduct->getSku()
                                    ),
                                ),
                            );
                            if (!$childProduct) {
                                continue;
                            }

                            $superGroup[(int)$childProduct->getId()] = $qtyOrdered;
                        }
                        $requestInfo->setData(key: 'super_group', value: $superGroup);
                        break;

                    case Configurable::TYPE_CODE:
                        $requestOptions = $requestInfo->getData(key: 'options') ?: [];
                        $requestInfo->unsetData(key: 'options');
                        $requestInfo->setData(key: 'product', value: $product->getId());

                        $superAttribute = [];
                        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
                        $typeInstance = $product->getTypeInstance();
                        $configurableAttributes = $typeInstance->getConfigurableAttributesAsArray(product: $product);
                        foreach ($requestOptions as $attributeCode => $value) {
                            /** @var Attribute $configurableAttribute */
                            $configurableAttribute = current(
                                array_filter(
                                    array: $configurableAttributes,
                                    callback: static fn (array $attribute): bool => (
                                        $attributeCode === $attribute['attribute_code']
                                    ),
                                ),
                            );

                            if (!$configurableAttribute) {
                                continue;
                            }

                            $superAttribute[$configurableAttribute['attribute_id']] = current(
                                array_column(
                                    array: array_filter(
                                        array: $configurableAttribute['options'],
                                        callback: static fn (array $option): bool => $option['label'] === $value,
                                    ),
                                    column_key: 'value',
                                ),
                            );
                        }
                        $requestInfo->setData(key: 'super_attribute', value: $superAttribute);
                        break;
                }

                $this->cart->addProduct(
                    productInfo: $product,
                    requestInfo: $requestInfo,
                );
            }
        }

        $this->cart->save();

        return $this->cart;
    }
}
