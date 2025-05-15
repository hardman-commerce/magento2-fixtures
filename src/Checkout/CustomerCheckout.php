<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

class CustomerCheckout
{
    final public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly QuoteManagement $quoteManagement,
        private readonly PaymentConfig $paymentConfig,
        private readonly Cart $cart,
        private ?int $shippingAddressId = null,
        private ?int $billingAddressId = null,
        private ?string $shippingMethodCode = null,
        private ?string $paymentMethodCode = null,
    ) {
    }

    public static function fromCart(Cart $cart): CustomerCheckout
    {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            addressRepository: $objectManager->create(type: AddressRepositoryInterface::class),
            quoteRepository: $objectManager->create(type: CartRepositoryInterface::class),
            quoteManagement: $objectManager->create(type: QuoteManagement::class),
            paymentConfig: $objectManager->create(type: PaymentConfig::class),
            cart: $cart,
        );
    }

    public function withCustomerBillingAddressId(int $addressId): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->billingAddressId = $addressId;

        return $checkout;
    }

    public function withCustomerShippingAddressId(int $addressId): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->shippingAddressId = $addressId;

        return $checkout;
    }

    public function withShippingMethodCode(string $code): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->shippingMethodCode = $code;

        return $checkout;
    }

    public function withPaymentMethodCode(string $code): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->paymentMethodCode = $code;

        return $checkout;
    }

    /**
     * Customer shipping address as configured or try default shipping address
     */
    private function getCustomerShippingAddressId(): int
    {
        return $this->shippingAddressId
               ?? (int)$this->cart->getCustomerSession()->getCustomer()->getDefaultShippingAddress()->getId();
    }

    /**
     * Customer billing address as configured or try default billing address
     */
    private function getCustomerBillingAddressId(): int
    {
        return $this->billingAddressId
               ?? (int)$this->cart->getCustomerSession()->getCustomer()->getDefaultBillingAddress()->getId();
    }

    /**
     * Shipping method code as configured, or try first available rate
     */
    private function getShippingMethodCode(): string
    {
        return $this->shippingMethodCode
               ?? $this->cart->getQuote()->getShippingAddress()->getAllShippingRates()[0]->getCode();
    }

    /**
     * Payment method code as configured, or try first available method
     */
    private function getPaymentMethodCode(): string
    {
        return $this->paymentMethodCode
               ?? array_values(array: $this->paymentConfig->getActiveMethods())[0]->getCode();
    }

    /**
     * @throws \Exception
     */
    public function placeOrder(): Order
    {
        $this->saveBilling();
        $this->saveShipping();
        $this->savePayment();
        /** @var Quote $reloadedQuote */
        $reloadedQuote = $this->quoteRepository->get(cartId: $this->cart->getQuote()->getId());
        // Collect missing totals, like shipping
        $reloadedQuote->collectTotals();
        $order = $this->quoteManagement->submit(quote: $reloadedQuote);
        if (!$order instanceof Order) {
            $returnType = is_object($order) ? get_class($order) : gettype($order);
            throw new \RuntimeException(message: 'QuoteManagement::submit() returned ' . $returnType . ' instead of Order');
        }
        $this->cart->getCheckoutSession()->clearQuote();

        return $order;
    }

    /**
     * @throws \Exception
     */
    private function saveBilling(): void
    {
        $billingAddress = $this->cart->getQuote()->getBillingAddress();
        $billingAddress->importCustomerAddressData(
            $this->addressRepository->getById(addressId: $this->getCustomerBillingAddressId()),
        );
        $billingAddress->save();
    }

    /**
     * @throws \Exception
     */
    private function saveShipping(): void
    {
        $shippingAddress = $this->cart->getQuote()->getShippingAddress();
        $shippingAddress->importCustomerAddressData(
            $this->addressRepository->getById(addressId: $this->getCustomerShippingAddressId()),
        );
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingAddress->setShippingMethod($this->getShippingMethodCode());
        $shippingAddress->save();
    }

    /**
     * @throws \Exception
     */
    private function savePayment(): void
    {
        $payment = $this->cart->getQuote()->getPayment();
        $payment->setMethod(method: $this->getPaymentMethodCode());
        $payment->save();
    }
}
