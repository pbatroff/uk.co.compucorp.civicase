<div id="civicaseContactTab-{$case_type_category}">
  <div class="container" ng-view></div>
</div>
{literal}
  <script type="text/javascript">
    (function(angular, $, _) {
      angular.module('civicaseContactTab', ['civicase']);
      angular.module('civicaseContactTab').config(function($routeProvider, currentCaseCategoryProvider) {
        var caseTypeCategoryId = '{/literal}{$case_type_category}{literal}';

        currentCaseCategoryProvider.set(caseTypeCategoryId);
        $routeProvider.when('/', {
          reloadOnSearch: false,
          template: '<civicase-contact-case-tab></civicase-contact-case-tab>'
        });
      });
    })(angular, CRM.$, CRM._);

    CRM.$(document).one('crmLoad', function(){
      angular.bootstrap(document.getElementById('civicaseContactTab-{/literal}{$case_type_category}{literal}'), ['civicaseContactTab']);
    });
  </script>
{/literal}
