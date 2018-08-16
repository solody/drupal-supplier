<?php

namespace Drupal\supplier\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\finance\Entity\Ledger;
use Drupal\finance\FinanceManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\supplier\CostManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\distribution\DistributionManagerInterface;

/**
 * Class OrderSubscriber.
 */
class OrderSubscriber implements EventSubscriberInterface {

  /**
   * @var FinanceManagerInterface
   */
  protected $financeManager;

  /**
   * @var CostManagerInterface
   */
  protected $costManager;

  /**
   * Constructs a new OrderSubscriber object.
   * @param FinanceManagerInterface $finance_manager
   */
  public function __construct(FinanceManagerInterface $finance_manager, CostManagerInterface $cost_manager) {
    $this->financeManager = $finance_manager;
    $this->costManager = $cost_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
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
  public function commerce_order_place_post_transition(WorkflowTransitionEvent $event) {
    /** @var Order $commerce_order */
    $commerce_order = $event->getEntity();
    // 为商家增加收入
    $account = $this->financeManager->getAccount($commerce_order->getStore()->getOwner(), SUPPLIER_FINANCE_ACCOUNT_TYPE);

    if ($account) {
      $amount = $this->costManager->computeOrderSupplierCost($commerce_order);
      if (!$amount->isZero()) {
        $this->financeManager->createLedger($account, Ledger::AMOUNT_TYPE_DEBIT, $amount, '订单[' . $commerce_order->id() . ']成交获得收入', $commerce_order);
      }
    }
  }

  /**
   * This method is called whenever the commerce_order.cancel.pre_transition event is
   * dispatched.
   *
   * @param WorkflowTransitionEvent $event
   */
  public function commerce_order_cancel_pre_transition(WorkflowTransitionEvent $event) {
    /** @var Order $commerce_order */
    $commerce_order = $event->getEntity();

    // 取消商家的收入
    $account = $this->financeManager->getAccount($commerce_order->getStore()->getOwner(), SUPPLIER_FINANCE_ACCOUNT_TYPE);

    if ($account) {
      $amount = $this->costManager->computeOrderSupplierCost($commerce_order);
      if (!$amount->isZero()) {
        $this->financeManager->createLedger($account, Ledger::AMOUNT_TYPE_CREDIT, $amount, '订单[' . $commerce_order->id() . ']取消，扣除收入', $commerce_order);
      }
    }
  }

}
