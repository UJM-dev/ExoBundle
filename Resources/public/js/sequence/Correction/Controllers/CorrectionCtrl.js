(function () {
    'use strict';

    angular.module('Correction').controller('CorrectionCtrl', [
        'CommonService',
        function (CommonService) {
            this.currentQuestion = {};
            this.currentAnswer = {};
            this.answers = {};
            this.sequence = {};
            this.isCollapsed = false;

            this.init = function (sequence, answers) {
                this.answers = answers;
                this.sequence = sequence;
                console.log('answers');
                console.log(this.answers);
                console.log('sequence');
                console.log(this.sequence);
            }

            this.setCurrentQuestion = function (question) {
                this.currentQuestion = question;
            };

            this.setCurrentAnswer = function (answer) {
                this.currentAnswer = answer;
            };

            this.goTo = function (index) {

            };

            this.toggleDetails = function (id) {
                console.log('toggle ' + id);
                $('#' + id).toggle();
            };

            this.getChoiceSimpleType = function (choice) {
                return CommonService.getObjectSimpleType(choice);
            };

            this.getStudentAnswer = function (choice, item) {
                if (item.multiple) {
                    var isSelected = item.answer[choice.id];
                    return isSelected;
                }
                else {
                    var id = parseInt(item.answer);
                    return id === choice.id;
                }
            };
            
            this.generateUrl = function (witch){                
                return CommonService.generateUrl(witch);
            };
        }
    ]);
})();