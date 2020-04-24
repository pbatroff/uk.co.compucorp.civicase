<?php

use CRM_Civicase_Factory_CaseTypeCategoryEventHandler as CaseTypeCategoryEventHandlerFactory;
use CRM_Civicase_Helper_CaseCategory as CaseCategoryHelper;

/**
 * CRM_Civicase_Upgrader_Steps_Step0012 class.
 */
class CRM_Civicase_Upgrader_Steps_Step0012 {

  /**
   * Performs Upgrade.
   */
  public function apply() {
    $this->updateCategoryMenuItems();

    return TRUE;
  }

  /**
   * Update Case Type category menu items.
   */
  private function updateCategoryMenuItems() {
    try {
      // Get all case type categories.
      // (but not default Cases category and not Awards or Prospects)
      $params = ['name' => ['NOT IN' => ['Cases', 'Awards', 'Prospecting']]];
      $categories = CaseCategoryHelper::getCategoriesByParams($params);

      // Recreate menu items.
      $handler = CaseTypeCategoryEventHandlerFactory::create();
      foreach ($categories as &$category) {
        $handler->onDelete($category['name']);
        $handler->onCreate($category['name']);
      }
    }
    catch (Exception $e) {
    }
  }

}
