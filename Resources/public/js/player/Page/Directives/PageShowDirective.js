/**
 * Activity Form directive
 * Directive Documentation : https://docs.angularjs.org/guide/directive
 */
(function () {
    'use strict';

    angular.module('Page').directive('pageShow', [
        function () {
            return {
                restrict: 'E',
                replace: true,
                controller: 'PageShowCtrl',
                controllerAs: 'pageShowCtrl',
                templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/player/Page/Partials/page.show.html',
                scope: {
                    pages: '='
                },
                link: function (scope, element, attr, pageShowCtrl) {
                    
                }
            };
        }
    ]);
})();


