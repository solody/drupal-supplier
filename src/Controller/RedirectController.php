<?php

namespace Drupal\supplier\Controller;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class TaskRedirectController.
 */
class RedirectController extends ControllerBase {

  public function supplierProfile() {
    if (in_array('supplier', $this->currentUser()->getRoles())) {
      $query = \Drupal::entityQuery('commerce_store');
      $query->condition('uid', $this->currentUser()->id());
      $entity_ids = $query->execute();

      if (empty($entity_ids)) return [
        '#markup' => '<h4>找不到商家资料。</h4>'
      ];

      return $this->redirect('entity.commerce_store.canonical', ['commerce_store' => array_pop($entity_ids)]);
    } else {
      throw new AccessDeniedHttpException('当前用户不是商家角色');
    }
  }

  public function supplierAccount() {
    if (in_array('supplier', $this->currentUser()->getRoles())) {
      $query = \Drupal::entityQuery('finance_account');
      $query->condition('user_id', $this->currentUser()->id());
      $entity_ids = $query->execute();

      if (empty($entity_ids)) return [
        '#markup' => '<h4>找不到商家账户。</h4>'
      ];

      return $this->redirect('entity.finance_account.canonical', ['finance_account' => array_pop($entity_ids)]);
    } else {
      throw new AccessDeniedHttpException('当前用户不是商家角色');
    }
  }

  /**
   * Owner.
   *
   * @param Store $commerce_store
   * @return string
   *   Return Hello string.
   */
  public function owner(Store $commerce_store) {
    $user = $commerce_store->getOwner();
    return $this->redirect('entity.user.edit_form', ['user' => $user->id()]);
  }


  public function financeAccount(Store $commerce_store) {
    $query = \Drupal::entityQuery('finance_account');
    $query->condition('user_id', $commerce_store->getOwnerId());
    $query->condition('type', SUPPLIER_FINANCE_ACCOUNT_TYPE);
    $entity_ids = $query->execute();

    if (empty($entity_ids)) return [
      '#markup' => '<h4>找不到记账账户。</h4>'
    ];

    return $this->redirect('entity.finance_account.canonical', ['finance_account' => array_pop($entity_ids)]);
  }
}
