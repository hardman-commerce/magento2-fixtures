<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Checkout\CartBuilder;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CreditmemoBuilderTest extends TestCase
{
    private OrderFixture $orderFixture;
    private CreditmemoRepositoryInterface $creditmemoRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditmemoRepository = Bootstrap::getObjectManager()->create(CreditmemoRepositoryInterface::class);
    }

    /**
     * @throws LocalizedException
     */
    protected function tearDown(): void
    {
        OrderFixtureRollback::create()->execute($this->orderFixture);

        parent::tearDown();
    }

    /**
     * Create a credit memo for all the order's items.
     *
     * @test
     * @throws \Exception
     */
    public function createCreditmemo(): void
    {
        $order = OrderBuilder::anOrder()->withPaymentMethod('checkmo')->build();
        $this->orderFixture = new OrderFixture($order);

        $refundFixture = new CreditmemoFixture(CreditmemoBuilder::forOrder($order)->build());

        self::assertInstanceOf(CreditmemoInterface::class, $this->creditmemoRepository->get($refundFixture->getId()));
        self::assertFalse($order->canCreditmemo());
    }

    /**
     * Create a credit memo for some of the order's items.
     *
     * @test
     * @throws \Exception
     */
    public function createPartialCreditmemos(): void
    {
        $order = OrderBuilder::anOrder()->withPaymentMethod('checkmo')->withProducts(
            ProductBuilder::aSimpleProduct()->withSku('foo'),
            ProductBuilder::aSimpleProduct()->withSku('bar')
        )->withCart(
            CartBuilder::forCurrentSession()
                ->withSimpleProduct('foo', 2)
                ->withSimpleProduct('bar', 3)
        )->build();
        $this->orderFixture = new OrderFixture($order);

        $orderItemIds = [];
        /** @var OrderItemInterface $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $orderItemIds[$orderItem->getSku()] = (int)$orderItem->getItemId();
        }

        $refundFixture = new CreditmemoFixture(
            CreditmemoBuilder::forOrder($order)
                ->withItem($orderItemIds['foo'], 2)
                ->withItem($orderItemIds['bar'], 2)
                ->build()
        );

        self::assertInstanceOf(CreditmemoInterface::class, $this->creditmemoRepository->get($refundFixture->getId()));
        self::assertTrue($order->canCreditmemo());

        $refundFixture = new CreditmemoFixture(
            CreditmemoBuilder::forOrder($order)
                ->withItem($orderItemIds['bar'], 1)
                ->build()
        );

        self::assertInstanceOf(CreditmemoInterface::class, $this->creditmemoRepository->get($refundFixture->getId()));
        self::assertFalse($order->canCreditmemo());
    }
}
