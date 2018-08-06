<?php

namespace Drupal\supplier\Controller;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Controller\ControllerBase;
use Drupal\finance\Entity\TransferMethod;
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

  public function supplierWithdraw() {
    if (in_array('supplier', $this->currentUser()->getRoles())) {
      $query = \Drupal::entityQuery('finance_account');
      $query->condition('user_id', $this->currentUser()->id());
      $entity_ids = $query->execute();

      if (empty($entity_ids)) return [
        '#markup' => '<h4>找不到商家账户。</h4>'
      ];

      $commerce_stores = \Drupal::entityTypeManager()
        ->getStorage('commerce_store')
        ->loadByProperties([
          'uid' => $this->currentUser()->id(),
          'type' => 'supplier'
        ]);

      if (count($commerce_stores)) {
        /** @var Store $commerce_store */
        $commerce_store = array_pop($commerce_stores);
        $bank_info = $commerce_store->get('field_store_bank_name')->value . ' ' .
          $commerce_store->get('field_store_bank_account_name')->value . ' ' .
          $commerce_store->get('field_store_bank_account_num')->value;

        // 检查商家是否有提现方法，没有则创建一个手动提现，提现说明内容是商家资料中填写的银行信息
        // 如果已有有，则更新提现说明
        $transfer_method_name = '线下银行账户转账（商家对公账户）';
        $transfer_methods = \Drupal::entityTypeManager()
          ->getStorage('finance_transfer_method')
          ->loadByProperties([
            'user_id' => $this->currentUser()->id(),
            'type' => 'manual',
            'name' => $transfer_method_name
          ]);

        $transfer_method = null;
        if (count($transfer_methods)) {
          // 更新
          /** @var TransferMethod $transfer_method */
          $transfer_method = array_pop($transfer_methods);
          $transfer_method->set('manual_remarks', $bank_info);
        } else {
          // 创建
          $transfer_method = TransferMethod::create([
            'user_id' => $this->currentUser()->id(),
            'type' => 'manual',
            'name' => $transfer_method_name,
            'manual_remarks' => $bank_info
          ]);
        }
        $transfer_method->save();
      }

      return $this->redirect('finance.finance_apply_withdraw_form', ['finance_account' => array_pop($entity_ids)]);
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
