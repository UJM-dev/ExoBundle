
(function () {
    'use strict';

    angular.module('Question').directive('choiceQuestion', [
        function () {
            return {
                restrict: 'E',
                replace: true,
                controller: 'ChoiceQuestionCtrl',
                controllerAs: 'choiceQuestionCtrl',
                templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/sequence/Player/Question/Partials/choice.question.html',
                scope: {
                    question: '=',
                    canSeeFeedback: '='
                },
                link: function (scope, element, attr, choiceQuestionCtrl) {
                    choiceQuestionCtrl.init(scope.question, scope.canSeeFeedback);                 
                    
                }
            };
        }
    ]);
})();

