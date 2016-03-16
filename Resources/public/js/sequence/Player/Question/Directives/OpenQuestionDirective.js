
import OpenQuestionCtrl from '../Controllers/OpenQuestionCtrl'
//import ExerciseService from '../../Exercise/Services/ExerciseService'
export default class OpenQuestion {
  constructor() {
    this.scope = {
      question: '=',
      canSeeFeedback: '='
    };
    this.restrict = 'E';
    this.template = require('../Partials/open.question.html');
    this.replace = true;
    this.controller = OpenQuestionCtrl;
    this.controllerAs = 'openQuestionCtrl';
    this.link = function (scope, element, attr, openQuestionCtrl) {
      //console.log(typeof jsPlumb);
      let jsPlumbPreviousConnections = jsPlumb.getConnections();
      if(jsPlumbPreviousConnections.length > 0){
        jsPlumb.detachEveryConnection();
        jsPlumb.deleteEveryEndpoint();
      }
      openQuestionCtrl.init(scope.question, scope.canSeeFeedback);
    }
  }
}
OpenQuestionCtrl.$inject = ['$ngBootbox', '$scope', 'CommonService', 'QuestionService', 'PlayerDataSharing']
//ExerciseService.$inject = ['$http', '$filter', '$q', '$window'];
