<?php

namespace Drupal\supplier;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;

/**
 * Interface CostManagerInterface.
 */
interface CostManagerInterface {
  /**
   * @param OrderInterface $commerce_order
   * @return Price
   */
  public function computeOrderSupplierCost (OrderInterface $commerce_order);
}
