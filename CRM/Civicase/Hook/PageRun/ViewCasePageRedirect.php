<?php

use CRM_Civicase_Helper_CaseCategory as CaseCategoryHelper;

/**
 * Class CRM_Civicase_Hook_PageRun_ViewCasePageRedirect.
 */
class CRM_Civicase_Hook_PageRun_ViewCasePageRedirect {

  /**
   * Redirects the core view case page to an angular page provided by civicase.
   *
   * @param object $page
   *   Page Object.
   */
  public function run(&$page) {
    $caseId = CRM_Utils_Request::retrieve('id', 'Positive');

    if (!$this->shouldRun($page, $caseId)) {
      return;
    }

    $this->redirectViewCasePage($caseId);
  }

  /**
   * Redirects the core view case page to an angular page provided by civicase.
   *
   * @param int $caseId
   *   Case Id.
   */
  private function redirectViewCasePage($caseId) {
    // OLD: http://localhost/civicrm/contact/view/case?reset=1&action=view&cid=129&id=51
    // NEW: http://localhost/civicrm/case/a/?ctc=case_category_name#/case/list?
    // sf=contact_id.sort_name&sd=ASC&focus=0&cf=%7B%7D&caseId=51&tab=summary&sx=0
    $case = civicrm_api3('Case', 'getsingle', [
      'id' => $caseId,
      'return' => [
        'case_type_id.name',
        'status_id',
        'case_type_id.case_type_category',
      ],
    ]);

    // Add selected parameters passed to this page to the redirect URL.
    $relevantUrlParams = [['name' => 'tab', 'type' => 'String']];
    $caseCategoryName = CaseCategoryHelper::getCaseCategoryNameFromOptionValue($case['case_type_id.case_type_category']);
    $caseCategoryId = CaseCategoryHelper::getCategoryByName($caseCategoryName, 'id');
    $fragment = "/case/list?sf=id&sd=DESC&caseId={$caseId}&cf=%7B%22ctc%22:%22" . strtolower($caseCategoryId) . "%22,%22status_id%22:%5B%22{$case['status_id']}%22%5D,%22case_type_id%22:%5B%22{$case['case_type_id.name']}%22%5D%7D";
    $this->addRelevantUrlParamsToFragment($fragment, $relevantUrlParams);
    $url = CRM_Utils_System::url('civicrm/case/a/', ['ctc' => strtolower($caseCategoryId)], TRUE, $fragment, FALSE);

    CRM_Utils_System::redirect($url);
  }

  /**
   * Parameters from the URL that we are intrested in appending to the fragment.
   *
   * @param string $fragment
   *   Fragment.
   * @param array $relevantUrlParams
   *   Url parameters.
   */
  private function addRelevantUrlParamsToFragment(&$fragment, array $relevantUrlParams) {
    foreach ($relevantUrlParams as $relevantUrlParam) {
      $value = CRM_Utils_Request::retrieve($relevantUrlParam['name'], $relevantUrlParam['type']);
      if ($value) {
        $fragment .= "&{$relevantUrlParam['name']}={$value}";
      }
    }
  }

  /**
   * Determines if the hook will run.
   *
   * @param object $page
   *   Page Object.
   * @param int $caseId
   *   Case Id.
   *
   * @return bool
   *   returns a boolean to determine if hook will run or not.
   */
  private function shouldRun($page, $caseId) {
    return $page instanceof CRM_Case_Page_Tab && $caseId;
  }

}
