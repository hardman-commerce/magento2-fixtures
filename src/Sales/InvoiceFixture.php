<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\InvoiceInterface;

class InvoiceFixture
{
    private InvoiceInterface $invoice;

    public function __construct(InvoiceInterface $shipment)
    {
        $this->invoice = $shipment;
    }

    public function getInvoice(): InvoiceInterface
    {
        return $this->invoice;
    }

    public function getId(): int
    {
        return (int)$this->invoice->getEntityId();
    }
}
