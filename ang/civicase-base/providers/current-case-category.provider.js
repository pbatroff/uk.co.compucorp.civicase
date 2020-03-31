(function (angular, $, _, CRM) {
  var module = angular.module('civicase-base');

  module.provider('currentCaseCategory', currentCaseCategoryProvider);

  /**
   * Current Case Category provider
   */
  function currentCaseCategoryProvider () {
    var currentCaseCategory = CRM['civicase-base'].currentCaseCategory;

    this.$get = $get;
    this.set = set;

    /**
     * @returns {string} the current case type category.
     */
    function $get () {
      return currentCaseCategory;
    }

    /**
     * Updates the value for the current case category
     *
     * @param {string} updatedCaseCategory the new case type category.
     */
    function set (updatedCaseCategory) {
      currentCaseCategory = updatedCaseCategory;
    }
  }
})(angular, CRM.$, CRM._, CRM);
