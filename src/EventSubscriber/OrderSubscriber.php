<?php

namespace Drupal\supplier\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\finance\Entity\Ledger;
use Drupal\finance\FinanceManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\distribution\DistributionManagerInterface;

/**
 * Class OrderSubscriber.
 */
class OrderSubscriber implements EventSubscriberInterface
{

    /**
     * @var FinanceManagerInterface
     */
    protected $financeManager;

    /**
     * Constructs a new OrderSubscriber object.
     * @param FinanceManagerInterface $finance_manager
     */
    public function __construct(FinanceManagerInterface $finance_manager)
    {
        $this->financeManager = $finance_manager;
    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents()
    {
        $events['commerce_order.place.post_transition'] = ['commerce_order_place_post_transition'];
        $events['commerce_order.cancel.pre_transition'] = ['commerce_order_cancel_pre_transition'];

        return $events;
    }

    /**
     * This method is called whenever the commerce_order.place.post_transition event is
     * dispatched.
     *
     * @param WorkflowTransitionEvent $event
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function commerce_order_place_post_transition(WorkflowTransitionEvent $event)
    {
        /** @var Order $commerce_order */
        $commerce_order = $event->getEntity();
        // 为商家增加收入
        $account = $this->financeManager->getAccount($commerce_order->getStore()->getOwner(), SUPPLIER_FINANCE_ACCOUNT_TYPE);

        if ($account) {
            $amount = new Price('0.00', $commerce_order->getTotalPrice()->getCurrencyCode());
            foreach ($commerce_order->getItems() as $order_item) {
                /** @var OrderItem $order_item */
                if (!$order_item->getPurchasedEntity()->get('cost')->isEmpty()) {
                    /** @var Price $cost */
                    $cost = $order_item->getPurchasedEntity()->get('cost')->first()->toPrice();
                    $cost = new Price((string)($order_item->getQuantity() * $cost->getNumber()), $cost->getCurrencyCode());
                    if ($cost->getCurrencyCode() === $amount->getCurrencyCode()) {
                        $amount = $amount->add($cost);
                    }
                }
            }

            if (!$amount->isZero()) {
                $this->financeManager->createLedger($account, Ledger::AMOUNT_TYPE_DEBIT, $amount, '订单['.$commerce_order->id().']成交获得收入', $commerce_order);
            }
        }
    }

    /**
     * This method is called whenever the commerce_order.cancel.pre_transition event is
     * dispatched.
     *
     * @param WorkflowTransitionEvent $event
     */
    public function commerce_order_cancel_pre_transition(WorkflowTransitionEvent $event)
    {
        // TODO: 取消商家的收入
    }

}
