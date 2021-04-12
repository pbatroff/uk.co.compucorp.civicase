<?php

class CRM_Civicase_Hook_BuildForm_AddCaseCategorySingularLabelField {
  public function run(CRM_Core_Form $form, $formName) {
    if (!$this->shouldRun($form)) {
      return;
    }

    $form->add(
      'text',
      'case_category_singular_label',
      ts('Singular Label')
    );

    $templatePath = CRM_Civicase_ExtensionUtil::path() . '/templates';
    CRM_Core_Region::instance('page-body')->add(
      [
        'template' => "{$templatePath}/CRM/Civicase/Form/CaseCategoryInstance.tpl",
      ]
    );
  }

  private function shouldRun(CRM_Core_Form $form) {
    $isOptionsForm = get_class($form) === CRM_Admin_Form_Options::class;
    $isCaseTypeCategoryOptionsForm = $form->get('gName') === 'case_type_categories';

    return $isOptionsForm && $isCaseTypeCategoryOptionsForm;
  }

}
