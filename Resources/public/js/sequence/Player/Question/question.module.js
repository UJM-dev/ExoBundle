
import OpenQuestion from './Directives/OpenQuestionDirective'
angular.module('Question', [

  ])
  .directive(
    'openQuestion', () => new OpenQuestion
  )
