/* eslint-env jasmine */

(() => {
  describe('Case Actions', () => {
    let CaseActions, CaseActionsData, currentCaseCategory;

    beforeEach(module('civicase.data', 'civicase'));

    beforeEach(inject((_CaseActions_, _CaseActionsData_, _currentCaseCategory_) => {
      CaseActions = _CaseActions_;
      CaseActionsData = _CaseActionsData_.values;
      currentCaseCategory = _currentCaseCategory_;
    }));

    describe('when getting all case actions for the current case category', () => {
      let returnedCaseActions;

      beforeEach(() => {
        returnedCaseActions = CaseActions.getAllForCurrentCaseCategory();
      });

      it('returns all the case actions for the current case category', () => {
        expect(returnedCaseActions).toEqual(CaseActionsData[currentCaseCategory]);
      });
    });
  });
})();
