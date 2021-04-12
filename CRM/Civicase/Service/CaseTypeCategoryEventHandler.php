<?php

use CRM_Civicase_Service_CaseCategoryCustomDataType as CaseCategoryCustomDataType;
use CRM_Civicase_Service_CaseCategoryCustomFieldExtends as CaseCategoryCustomFieldExtends;
use CRM_Civicase_Service_CaseCategoryInstanceUtils as CaseCategoryInstance;

/**
 * Handles events when case type category is created/updated/deleted.
 */
class CRM_Civicase_Service_CaseTypeCategoryEventHandler {

  /**
   * Menu handler.
   *
   * @var \CRM_Civicase_Service_CaseCategoryMenu
   */
  protected $menu;

  /**
   * Custom data handler.
   *
   * @var \CRM_Civicase_Service_CaseCategoryCustomDataType
   */
  protected $customData;

  /**
   * Custom field handler.
   *
   * @var \CRM_Civicase_Service_CaseCategoryCustomFieldExtends
   */
  protected $customFieldExtends;

  /**
   * CRM_Civicase_Service_CaseTypeCategoryEventHandler constructor.
   *
   * @param \CRM_Civicase_Service_CaseCategoryCustomDataType $customData
   *   Custom data handler.
   * @param \CRM_Civicase_Service_CaseCategoryCustomFieldExtends $customFieldExtends
   *   Custom field handler.
   */
  public function __construct(CaseCategoryCustomDataType $customData, CaseCategoryCustomFieldExtends $customFieldExtends) {
    $this->customData = $customData;
    $this->customFieldExtends = $customFieldExtends;
  }

  /**
   * Perform actions on case type category create.
   *
   * @param CRM_Civicase_Service_CaseCategoryInstanceUtils $caseCategoryInstance
   *   Case category instance utilities class.
   * @param string $caseCategoryName
   *   Case type category name.
   */
  public function onCreate(CaseCategoryInstance $caseCategoryInstance, $caseTypeCategory) {
    if (empty($caseTypeCategory['label'])) {
      return;
    }

    $menu = $caseCategoryInstance->getMenuObject();
    $menu->createItems($caseTypeCategory);
    $this->customFieldExtends->create(
      $caseCategory['label'],
      "Case ({$caseCategory['label']})",
      $caseCategoryInstance->getCustomGroupEntityTypesFunction()
    );
    $this->customData->create($caseCategory['label']);
  }

  /**
   * Perform actions on case type category update.
   *
   * @param CRM_Civicase_Service_CaseCategoryInstanceUtils $caseCategoryInstance
   *   Case category instance utilities class.
   * @param int $caseCategoryId
   *   Case type category id.
   * @param bool $caseCategoryStatus
   *   (Optional) Case type category status (enabled / disabled).
   * @param string $caseCategoryIcon
   *   (Optional) Case type category icon.
   */
  public function onUpdate(CaseCategoryInstance $caseCategoryInstance, $caseCategoryId, $caseCategoryStatus = NULL, $caseCategoryIcon = NULL) {
    if (!$caseCategoryId) {
      return;
    }

    $updateParams = [];
    if (isset($caseCategoryStatus)) {
      $updateParams['is_active'] = !empty($caseCategoryStatus) ? 1 : 0;
    }
    if (isset($caseCategoryIcon)) {
      $updateParams['icon'] = 'crm-i ' . $caseCategoryIcon;
    }

    if (empty($updateParams)) {
      return;
    }

    $menu = $caseCategoryInstance->getMenuObject();
    $menu->updateItems($caseCategoryId, $updateParams);
  }

  /**
   * Removes case type category menu item from the civicrm navigation bar.
   *
   * @param CRM_Civicase_Service_CaseCategoryInstanceUtils $caseCategoryInstance
   *   Case category instance utilities class.
   * @param string $caseCategoryName
   *   Case type category name.
   */
  public function onDelete(CaseCategoryInstance $caseCategoryInstance, $caseTypeCategory) {
    if (!isset($caseTypeCategory['name'])) {
      return;
    }
    $menu = $caseCategoryInstance->getMenuObject();
    $menu->deleteItems($caseTypeCategory['name']);
    $this->customFieldExtends->delete($caseTypeCategory['name']);
    $this->customData->delete($caseTypeCategory['name']);
    $this->deleteCategoryInstanceRelationship($caseCategoryInstance);
  }

  /**
   * Delete the entry holding the relationship between category and instance.
   *
   * @param CRM_Civicase_Service_CaseCategoryInstanceUtils $caseCategoryInstance
   *   Case category instance utilities class.
   */
  private function deleteCategoryInstanceRelationship(CaseCategoryInstance $caseCategoryInstance) {
    try {
      civicrm_api3('CaseCategoryInstance', 'delete', [
        'id' => $caseCategoryInstance->getCaseCategoryInstanceKey(),
      ]);
    }
    catch (Exception $e) {
    }
  }

}
