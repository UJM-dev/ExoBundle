
import OpenQuestionCtrl from '../Controllers/OpenQuestionCtrl'

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
