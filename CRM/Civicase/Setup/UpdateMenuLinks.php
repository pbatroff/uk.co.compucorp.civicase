<?php

use CRM_Civicase_Helper_CaseCategory as CaseCategoryHelper;

/**
 * Updates the Manage Cases Menu URLs.
 */
class CRM_Civicase_Setup_UpdateMenuLinks {

  /**
   * Manage case URL.
   */
  const MANAGE_CASE_URL = 'civicrm/case/a/?p=mg&ctc=@id#/case/list?cf=%7B"ctc":"@id"%7D';

  /**
   * Updates the Manage Cases Menu URLs.
   */
  public function apply() {
    $this->updateManageCasesMenuLink();
  }

  /**
   * Updates the Manage Cases Menu URL.
   *
   * To filter with `cases` case type category.
   */
  private function updateManageCasesMenuLink() {
    $casesParentMenu = civicrm_api3('Navigation', 'getsingle', [
      'name' => 'cases',
    ]);

    if ($casesParentMenu['id']) {
      $manageCasesMenuItem = civicrm_api3('Navigation', 'getsingle', [
        'name' => 'Manage Cases',
        'parent_id' => $casesParentMenu['id'],
      ]);
    }

    if ($manageCasesMenuItem['id']) {
      $categoryId = CaseCategoryHelper::getCategoryByName('cases', 'id');
    }

    if (!empty($categoryId)) {
      civicrm_api3('Navigation', 'create', [
        'id' => $manageCasesMenuItem['id'],
        'url' => str_replace('@id', $categoryId, self::MANAGE_CASE_URL),
      ]);
    }

  }

}
