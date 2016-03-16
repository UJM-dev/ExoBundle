/**
 * ExercisePlayerApp
 */

import ExercisePlayer from './Directives/player.directive'
import OpenQuestion from '../Question/Directives/OpenQuestionDirective'

import PlayerDataSharing from '../Shared/Services/PlayerDataSharing'
import ExerciseService from './Services/ExerciseService'
import CommonService from '../../Common/Services/CommonService'
import QuestionService from '../Question/Services/QuestionService'

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
  .directive(
    'exercisePlayer', () => new ExercisePlayer
  )
  .directive(
    'openQuestion', () => new OpenQuestion
  )
	.service('PlayerDataSharing', () => new PlayerDataSharing)
  .service('CommonService', CommonService)
  .service('QuestionService', QuestionService)
  .service('ExerciseService', ExerciseService)
  .filter(
    'unsafe',
    function($sce) {
      return $sce.trustAsHtml;
    }
  );
