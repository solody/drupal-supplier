<?php

namespace Drupal\supplier;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

/**
 * Class CostManager.
 */
class CostManager implements CostManagerInterface {

  /**
   * Constructs a new CostManager object.
   */
  public function __construct() {

  }

  /**
   * @inheritdoc
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function computeOrderSupplierCost (OrderInterface $commerce_order) {
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
    return $amount;
  }

}
