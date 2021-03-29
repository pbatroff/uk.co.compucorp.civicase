<?php


class CRM_Civicase_Upgrader_Steps_Step0014 {

  public function apply() {
    civicrm_api3('Dashboard', 'create', [
      'name' => 'mycaseactivities',
      'label' => 'My Case Activities',
      'url' => '',
      'fullscreen_url' => '',
      'permission' => 'access all cases and activities,access my cases and activities',
      'permission_operator' => 'OR',
      'is_active' => '1',
      'is_reserved' => '1',
      'cache_minutes' => '7200',
      'directive' => 'civicase-my-activities-dashlet',
    ]);

    return TRUE;
  }

}
