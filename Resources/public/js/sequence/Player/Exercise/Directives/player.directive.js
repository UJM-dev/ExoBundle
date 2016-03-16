
import ExercisePlayerCtrl from '../Controllers/ExercisePlayerCtrl'
// import ExerciseService from '../Services/ExerciseService'

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
        exercisePlayerCtrl.init(scope.paper, scope.exercise, scope.user, scope.currentStepIndex);
    }
  }
}

ExercisePlayerCtrl.$inject = ['$window', '$scope', 'ExerciseService', 'CommonService', 'PlayerDataSharing']

// ExerciseService.$inject = ['$http', '$filter', '$q', '$window'];
