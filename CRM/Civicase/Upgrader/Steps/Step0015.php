<?php

class CRM_Civicase_Upgrader_Steps_Step0015 {

  public function apply() {
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_case_category_instance
      ADD COLUMN singular_label varchar(255) character set utf8mb4 COLLATE utf8mb4_unicode_ci
        DEFAULT NULL
    ");

    return TRUE;
  }

}
