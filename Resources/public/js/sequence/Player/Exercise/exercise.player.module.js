/**
 * ExercisePlayerApp
 */

//import Common from '../../Common/common.module'
//import PlayerSharedServices from '../Shared/player.shared.services.module'
//import Question from '../Question/question.module'
import ExercisePlayer from './Directives/player.directive'
import PlayerDataSharing from '../Shared/Services/PlayerDataSharing'

import CommonService from '../../Common/Services/CommonService'
import QuestionService from '../Question/Services/QuestionService'
import OpenQuestion from '../Question/Directives/OpenQuestionDirective'

const dependencies = [
  'ngSanitize',
  'ngRoute',
  'angular-loading-bar',
  'ui.bootstrap',
  'ui.translation',
  'ngBootbox'
];
// exercise player module
angular.module('ExercisePlayerApp', dependencies)
  .config([
    'cfpLoadingBarProvider',
    function ExercisePlayerAppConfig(cfpLoadingBarProvider) {

      // please wait spinner config
      cfpLoadingBarProvider.latencyThreshold = 200;
      cfpLoadingBarProvider.includeBar = false;
      cfpLoadingBarProvider.spinnerTemplate = '<div class="loading">Loading&#8230;</div>';
    }
  ])
  //.directive('clarolinesearch', () => new ClarolineSearchDirective)
  .directive(
    'exercisePlayer', () => new ExercisePlayer
  )
  .directive(
    'openQuestion', () => new OpenQuestion
  )
	.service('PlayerDataSharing', () => new PlayerDataSharing)
  .service('CommonService', () => new CommonService)
  .service('QuestionService', () => new QuestionService)
  .filter(
    'unsafe',
    function($sce) {
      return $sce.trustAsHtml;
    }
  );
