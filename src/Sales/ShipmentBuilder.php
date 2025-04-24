<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class ShipmentBuilder
{
    private ShipmentItemCreationInterfaceFactory $itemFactory;
    private ShipmentTrackCreationInterfaceFactory $trackFactory;
    private ShipOrderInterface $shipOrder;
    private ShipmentRepositoryInterface $shipmentRepository;
    private Order $order;
    /**
     * @var int[]
     */
    private array $orderItems;
    /**
     * @var string[]
     */
    private array $trackingNumbers;

    final public function __construct(
        ShipmentItemCreationInterfaceFactory $itemFactory,
        ShipmentTrackCreationInterfaceFactory $trackFactory,
        ShipOrderInterface $shipOrder,
        ShipmentRepositoryInterface $shipmentRepository,
        Order $order,
    ) {
        $this->itemFactory = $itemFactory;
        $this->trackFactory = $trackFactory;
        $this->shipOrder = $shipOrder;
        $this->shipmentRepository = $shipmentRepository;
        $this->order = $order;

        $this->orderItems = [];
        $this->trackingNumbers = [];
    }

    public static function forOrder(
        Order $order,
    ): ShipmentBuilder {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            $objectManager->create(ShipmentItemCreationInterfaceFactory::class),
            $objectManager->create(ShipmentTrackCreationInterfaceFactory::class),
            $objectManager->create(ShipOrderInterface::class),
            $objectManager->create(ShipmentRepositoryInterface::class),
            $order,
        );
    }

    public function withItem(int $orderItemId, int $qty): ShipmentBuilder
    {
        $builder = clone $this;
        $builder->orderItems[$orderItemId] = $qty;

        return $builder;
    }

    public function withTrackingNumbers(string ...$trackingNumbers): ShipmentBuilder
    {
        $builder = clone $this;
        $builder->trackingNumbers = $trackingNumbers;

        return $builder;
    }

    public function build(): ShipmentInterface
    {
        $shipmentItems = $this->buildShipmentItems();
        $tracks = $this->buildTracks();

        $shipmentId = $this->shipOrder->execute(
            $this->order->getEntityId(),
            $shipmentItems,
            false,
            false,
            null,
            $tracks,
        );

        $shipment = $this->shipmentRepository->get($shipmentId);
        if (!empty($this->trackingNumbers)) {
            $shipment->setShippingLabel('%PDF-1.4');
            $this->shipmentRepository->save($shipment);
        }

        return $shipment;
    }

    /**
     * @return ShipmentTrackCreationInterface[]
     */
    private function buildTracks(): array
    {
        return array_map(
            function (string $trackingNumber): ShipmentTrackCreationInterface {
                $carrierCode = strtok((string)$this->order->getShippingMethod(), '_');
                $track = $this->trackFactory->create();
                $track->setCarrierCode($carrierCode);
                $track->setTitle($carrierCode);
                $track->setTrackNumber($trackingNumber);

                return $track;
            },
            $this->trackingNumbers,
        );
    }

    /**
     * @return ShipmentItemCreationInterface[]
     */
    private function buildShipmentItems(): array
    {
        $shipmentItems = [];

        foreach ($this->orderItems as $orderItemId => $qty) {
            $shipmentItem = $this->itemFactory->create();
            $shipmentItem->setOrderItemId($orderItemId);
            $shipmentItem->setQty($qty);
            $shipmentItems[] = $shipmentItem;
        }

        return $shipmentItems;
    }
}
