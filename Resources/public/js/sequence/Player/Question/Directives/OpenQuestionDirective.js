
import OpenQuestionCtrl from '../Controllers/OpenQuestionCtrl'
export default class OpenQuestion {
  constructor() {
    this.scope = {
      question: '=',
      canSeeFeedback: '='
    };
    this.restrict = 'E';
    this.template = require('../Partials/open.question.html'); //'bundles/ujmexo/js/sequence/Player/Exercise/Partials/player.directive.html',
    this.replace = true;
    this.controller = OpenQuestionCtrl;
    this.controllerAs = 'openQuestionCtrl';
    this.link = function (scope, element, attr, openQuestionCtrl) {
      jsPlumb.detachEveryConnection();
      jsPlumb.deleteEveryEndpoint();
      openQuestionCtrl.init(scope.question, scope.canSeeFeedback);
    }
  }
}

/*

export default class ExercisePlayer {
  constructor() {
    this.scope = {
      paper: '=',
      exercise: '=',
      user: '=',
      currentStepIndex: '='};
    this.restrict = 'E';
    this.template = require('../Partials/player.directive.html'); //'bundles/ujmexo/js/sequence/Player/Exercise/Partials/player.directive.html',
    this.replace = true;
    this.controller = ExercisePlayerCtrl;
    this.controllerAs = 'exercisePlayerCtrl';
    this.link = function (scope, element, attr, exercisePlayerCtrl) {
        console.log('yep');
        exercisePlayerCtrl.init(scope.paper, scope.exercise, scope.user, scope.currentStepIndex);
    }
  }
}

ExercisePlayerCtrl.$inject = ['$window', '$scope', 'PlayerDataSharing']
*/
    angular.module('Question').directive('openQuestion', [
        function () {
            return {
                restrict: 'E',
                replace: true,
                controller: 'OpenQuestionCtrl',
                controllerAs: 'openQuestionCtrl',
                templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/sequence/Player/Question/Partials/open.question.html',
                scope: {
                    question: '=',
                    canSeeFeedback: '='
                },
                link: function (scope, element, attr, openQuestionCtrl) {
                    jsPlumb.detachEveryConnection();
                    jsPlumb.deleteEveryEndpoint();
                    openQuestionCtrl.init(scope.question, scope.canSeeFeedback);

                }
            };
        }
    ]);
})();
