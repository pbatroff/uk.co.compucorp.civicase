(function (angular, configuration) {
  var module = angular.module('civicase-base');

  module
    .constant('allowCaseLocks', configuration.allowCaseLocks)
    .constant('allowMultipleCaseClients', configuration.allowMultipleCaseClients);
})(angular, CRM['civicase-base']);
