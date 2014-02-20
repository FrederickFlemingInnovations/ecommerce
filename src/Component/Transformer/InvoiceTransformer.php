<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Component\Transformer;

use Sonata\Component\Delivery\Pool as DeliveryPool;
use Sonata\Component\Invoice\InvoiceElementInterface;
use Sonata\Component\Transformer\BaseTransformer;
use Sonata\Component\Order\OrderInterface;
use Sonata\Component\Invoice\InvoiceInterface;
use Sonata\Component\Invoice\InvoiceElementManagerInterface;
use Sonata\Component\Order\OrderElementInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class InvoiceTransformer extends BaseTransformer
{
    /**
     * @var InvoiceElementManagerInterface
     */
    protected $invoiceElementManager;

    /**
     * @var DeliveryPool
     */
    protected $deliveryPool;

    /**
     * Constructor
     *
     * @param InvoiceElementManagerInterface $invoiceElementManager Invoice element manager
     * @param DeliveryPool                   $deliveryPool          Delivery pool component
     */
    public function __construct(InvoiceElementManagerInterface $invoiceElementManager, DeliveryPool $deliveryPool)
    {
        $this->invoiceElementManager = $invoiceElementManager;
        $this->deliveryPool          = $deliveryPool;
    }

    /**
     * Transforms an order into an invoice
     *
     * @param OrderInterface   $order
     * @param InvoiceInterface $invoice
     */
    public function transformFromOrder(OrderInterface $order, InvoiceInterface $invoice)
    {
        $invoice->setName($order->getBillingName());
        $invoice->setAddress1($order->getBillingAddress1());
        $invoice->setAddress2($order->getBillingAddress2());
        $invoice->setAddress3($order->getBillingAddress3());
        $invoice->setCity($order->getBillingCity());
        $invoice->setCountry($order->getBillingCountryCode());
        $invoice->setPostcode($order->getBillingPostcode());

        $invoice->setEmail($order->getBillingEmail());
        $invoice->setFax($order->getBillingFax());
        $invoice->setMobile($order->getBillingMobile());
        $invoice->setPhone($order->getBillingPhone());
        $invoice->setReference($order->getReference());

        $invoice->setCurrency($order->getCurrency());
        $invoice->setCustomer($order->getCustomer());
        $invoice->setTotalExcl($order->getTotalExcl());
        $invoice->setTotalInc($order->getTotalInc());

        $invoice->setPaymentMethod($order->getPaymentMethod());

        $invoice->setLocale($order->getLocale());

        foreach ($order->getOrderElements() as $orderElement) {
            $invoiceElement = $this->createInvoiceElementFromOrderElement($orderElement);
            $invoiceElement->setInvoice($invoice);
            $invoice->addInvoiceElement($invoiceElement);
        }

        if ($order->getDeliveryCost() > 0) {
            $this->addDelivery($invoice, $order);
        }

        $invoice->setStatus(InvoiceInterface::STATUS_OPEN);
    }

    /**
     * Adds the delivery information from $order to $invoice
     *
     * @param InvoiceInterface $invoice
     * @param OrderInterface   $order
     */
    protected function addDelivery(InvoiceInterface $invoice, OrderInterface $order)
    {
        /** @var InvoiceElementInterface $invoiceElement */
        $invoiceElement = $this->invoiceElementManager->create();

        $invoiceElement->setQuantity(1);
        $invoiceElement->setPrice($order->getDeliveryCost());
        $invoiceElement->setUnitPrice($order->getDeliveryCost());
        $invoiceElement->setTotal($order->getDeliveryCost());
        $invoiceElement->setVatRate(0);

        $invoiceElement->setDesignation($this->deliveryPool->getMethod($order->getDeliveryMethod())->getName());
        $invoiceElement->setDescription($this->deliveryPool->getMethod($order->getDeliveryMethod())->getName());

        $invoiceElement->setInvoice($invoice);
        $invoice->addInvoiceElement($invoiceElement);
    }

    /**
     * Creates an InvoiceElement based on an OrderElement
     *
     * @param OrderElementInterface $orderElement
     *
     * @return \Sonata\Component\Invoice\InvoiceElementInterface
     */
    protected function createInvoiceElementFromOrderElement(OrderElementInterface $orderElement)
    {
        $invoice = $this->invoiceElementManager->create();
        $invoice->setOrderElement($orderElement);
        $invoice->setDescription($orderElement->getDescription());
        $invoice->setDesignation($orderElement->getDesignation());
        $invoice->setPrice($orderElement->getPrice(false));
        $invoice->setUnitPrice($orderElement->getUnitPrice(false));
        $invoice->setPriceIncludingVat(false);
        $invoice->setVatRate($orderElement->getVatRate());
        $invoice->setQuantity($orderElement->getQuantity());
        $invoice->setTotal($orderElement->getTotal(false));

        return $invoice;
    }
}