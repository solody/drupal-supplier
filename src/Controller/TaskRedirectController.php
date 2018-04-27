<?php

namespace Drupal\supplier\Controller;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class TaskRedirectController.
 */
class TaskRedirectController extends ControllerBase
{

    /**
     * Owner.
     *
     * @param Store $commerce_store
     * @return string
     *   Return Hello string.
     */
    public function owner(Store $commerce_store)
    {
        $user = $commerce_store->getOwner();
        return $this->redirect('entity.user.edit_form', ['user' => $user->id()]);
    }


    public function financeAccount(Store $commerce_store)
    {
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
