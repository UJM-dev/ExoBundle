/**
 * This is a container app used to handle routes for paper views
 * 
 */
(function () {
    'use strict';

    // exercise papers module
    var papersApp = angular.module('PapersApp', [
        'ngSanitize',
        'angular-loading-bar',
        'ngRoute',
        'ui.bootstrap',
        'ui.tinymce',
        'ui.translation',
        'ui.resourcePicker',
        'ngBootbox',
        'angular-table',
        'Common',
        'Paper'
    ]);


    var resolvePaperDetailsData = {
        /**
         * Get the paper details
         */
        paperPromise: [
            '$route',
            'PapersService',
            function getPaper($route, PapersService) {                             

                var promise = null;
                if ($route.current.params && $route.current.params.pid) {
                    promise = PapersService.getOne($route.current.params.pid);
                    
                }
                return promise;

            }
        ]
    };
    
    var resolvePaperListData = {
         /**
         * Get the exercise papers
         */
        paperListPromise: [
            '$route',
            'PapersService',
            function getPapers($route, PapersService) {                             

                var promise = null;
                if ($route.current.params && $route.current.params.eid) {
                    promise = PapersService.getAll($route.current.params.eid);
                    
                }
                return promise;

            }
        ]
    };


    papersApp.config([
        '$routeProvider',
        '$locationProvider',
        'cfpLoadingBarProvider',
        function PapersModuleConfig($routeProvider, $locationProvider, cfpLoadingBarProvider) {
            $routeProvider
                    .when('/:eid', {
                        templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/sequence/Papers/Partials/papers.list.html',
                        controller: 'PaperListCtrl',
                        controllerAs: 'paperListCtrl',
                        resolve: resolvePaperListData
                    })
                    .when('/:eid/:pid', {
                        templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/sequence/Papers/Partials/paper.show.html',
                        controller: 'PaperDetailsCtrl',
                        controllerAs: 'paperDetailsCtrl',
                        resolve: resolvePaperDetailsData
                    })
                    .otherwise({
                        redirectTo: '/:eid'
                    });
            //$locationProvider.html5Mode({enabled:true, requireBase:false});
            // please wait spinner config
            cfpLoadingBarProvider.latencyThreshold = 200;
            cfpLoadingBarProvider.includeBar = false;
            cfpLoadingBarProvider.spinnerTemplate = '<div class="loading">Loading&#8230;</div>';
        }
    ]);
    

    papersApp.filter(
            'unsafe',
            function ($sce) {
                return $sce.trustAsHtml;
            });
})();