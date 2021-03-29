(function (angular) {
  var module = angular.module('civicase');

  console.log('defined');
  module.directive('civicaseMyActivitiesDashlet', function () {
    return {
      templateUrl: '~/civicase/civicase-my-activities-dashlet.directive.html'
    };
  });
})(angular);
