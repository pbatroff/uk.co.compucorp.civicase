/* eslint-env jasmine */

((CivicaseSettings) => {
  describe('Current Case Category Provider', () => {
    let currentCaseCategory;

    describe('when getting the current case type category', () => {
      beforeEach(module('civicase-base'));

      beforeEach(inject((_currentCaseCategory_) => {
        currentCaseCategory = _currentCaseCategory_;
      }));

      it('defines the current case category as provided by the civicase settings global object', () => {
        expect(currentCaseCategory).toBe(CivicaseSettings.currentCaseCategory);
      });
    });

    describe('when updating the current case type category using providers', () => {
      const mockCaseCategory = 'mock-case-category';

      beforeEach(module('civicase-base', (currentCaseCategoryProvider) => {
        currentCaseCategoryProvider.set(mockCaseCategory);
      }));

      beforeEach(inject((_currentCaseCategory_) => {
        currentCaseCategory = _currentCaseCategory_;
      }));

      it('defines the current case category as the one defined using the provider', () => {
        expect(currentCaseCategory).toBe(mockCaseCategory);
      });
    });
  });
})(CRM['civicase-base']);
