<?php

/**
 * CaseCategoryInstance Post Process Hook class.
 */
class CRM_Civicase_Hook_PostProcess_SaveCaseCategoryInstance extends CRM_Civicase_Hook_CaseCategoryInstanceBase {

  /**
   * Saves the case category instance type relationship.
   *
   * @param string $formName
   *   The Form class name.
   * @param CRM_Core_Form $form
   *   The Form instance.
   */
  public function run($formName, CRM_Core_Form $form) {
    if (!$this->shouldRun($form, $formName)) {
      return;
    }

    $caseCategoryValues = $form->getVar('_submitValues');
    $instanceTypeValue = $caseCategoryValues[self::INSTANCE_TYPE_FIELD_NAME];
    $caseCategoryValue = $caseCategoryValues['value'];
    $this->saveCaseCategoryInstance([
      'category_id' => $caseCategoryValue,
      'instance_id' => $instanceTypeValue,
      'singular_label' => $caseCategoryValues['case_category_singular_label'],
    ]);
  }

  /**
   * Saves the case category instance type relationship.
   *
   * @param int $caseCategoryValue
   *   Case category value.
   * @param int $instanceTypeValue
   *   Instance Type Value.
   */
  private function saveCaseCategoryInstance($params) {
    $result = civicrm_api3('CaseCategoryInstance', 'get', [
      'category_id' => $params['category_id'],
    ]);

    if ($result['count'] === 1) {
      $params['id'] = $result['id'];
    }

    civicrm_api3('CaseCategoryInstance', 'create', $params);
  }

}
