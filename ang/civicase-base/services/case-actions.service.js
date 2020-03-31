(function (angular, $, _, CivicaseSettings) {
  var module = angular.module('civicase-base');

  module.service('CaseActions', CaseActions);

  /**
   * Case Actions Service.
   *
   * @param {string} currentCaseCategory the current case category.
   */
  function CaseActions (currentCaseCategory) {
    var defaultCaseActions = CivicaseSettings.caseActions[''];
    var caseCategoryActions = CivicaseSettings.caseActions[currentCaseCategory];

    this.getAllForCurrentCaseCategory = getAllForCurrentCaseCategory;

    /**
     * Get all Case actions for the current case category. If no actions are defined for
     * the current case category, the default ones are returned instead.
     *
     * @returns {object[]} a list of case actions.
     */
    function getAllForCurrentCaseCategory () {
      return caseCategoryActions || defaultCaseActions;
    }
  }
})(angular, CRM.$, CRM._, CRM.civicase);
