/**
 * Exercise Directive
 * Displays the summary of the Exercise and the links to the available actions for current User
 * @constructor
 */
var ExerciseDirective = function ExerciseDirective() {
    return {
        restrict: 'E',
        replace: true,
        controller: 'ExerciseCtrl',
        controllerAs: 'exerciseCtrl',
        templateUrl: AngularApp.webDir + 'bundles/ujmexo/js/angular/Exercise/Partials/exercise.html',
        scope: {
            exercise       : '=', // The current Exercise to display
            nbPapers       : '@', // The numbers of Papers submitted for this Exercise
            editEnabled    : '@', // User is allowed to edit current exercise ?
            composeEnabled : '@'  // Use is allowed to do (or retry) the Exercise
        },
        bindToController: true
    };
};

// Set up dependency injection
ExerciseDirective.$inject = [];

// Register directive into AngularJS
angular
    .module('Exercise')
    .directive('exercise', ExerciseDirective);
