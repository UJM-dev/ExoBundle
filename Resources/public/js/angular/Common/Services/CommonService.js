/**
 * CommonService
 */
angular.module('Common').factory('CommonService', [
    '$http',
    '$filter',
    '$q',
    function CommonService($http, $filter, $q) {
        this.paper = {};
        this.currentQuestion = {};
        this.currentAnswer = {};
        this.currentQuestionPaperData = {};

        return {
            /**
             * @param {object} object a javascript object with meta property
             * @returns null or string
             */
            objectHasOtherMeta: function (object) {
                if (!object.meta || object.meta === undefined || object.meta === 'undefined') {
                    return null;
                }
                return object.meta.licence || object.meta.created || object.meta.modified || (object.meta.description && object.meta.description !== '');
            },

            setPaper: function (paper) {
                this.paper = paper;
                return this.paper;
            },
            getPaper: function () {
                return this.paper;
            },

            /**
             * Calculate the global score for a paper
             * @param {type} paper
             * @param {type} questions
             * @returns {Number}
             */
            getPaperScore: function (paper, questions) {
                var score = 0.0; // final score
                var totalPoints = this.getExerciseTotalScore(questions);
                var studentPoints = 0.0; // good answers

                for (var i = 0; i < paper.questions.length; i++) {
                    // paper question item contains student answer, used hints
                    var currentPaperQuestion = paper.questions[i];

                    // for each given answer
                    if (currentPaperQuestion.answer) {
                        for (var j = 0; j < currentPaperQuestion.answer.length; j++) {
                            var id = currentPaperQuestion.answer[j];
                            studentPoints += this.getAnswerScore(id, questions);
                        }
                        // for each used hints
                        for (var k = 0; k < currentPaperQuestion.hints.length; k++) {
                            // find hint penalty in questions collection
                            var penalty = this.getHintPenalty(currentPaperQuestion.hints[k], questions);
                            // remove penalty value from student points
                            studentPoints -= penalty;
                        }
                    }
                }
                score = studentPoints * 20 / totalPoints;
                return score > 0 ? (Math.round(score / 0.5) * 0.5) : 0;
            },
            /**
             * get available score in the exercise
             * @param {type} questions
             * @returns {Number}
             */
            getExerciseTotalScore: function (questions) {
                var nbQuestions = questions.length;
                var score = 0.0;
                for (var i = 0; i < nbQuestions; i++) {
                    var currentQuestion = questions[i];
                    // update exercise total points
                    for (var j = 0; j < currentQuestion.solutions.length; j++) {
                        // update total points for the sequence
                        score += currentQuestion.solutions[j].score;
                    }
                }
                return score;
            },
            getHintPenalty: function (searched, questions) {
                var nbQuestions = questions.length;
                var penalty = 0.0;
                for (var i = 0; i < nbQuestions; i++) {
                    var currentQuestion = questions[i];
                    if (currentQuestion.hints) {
                        // update exercise total points
                        for (var j = 0; j < currentQuestion.hints.length; j++) {
                            if (currentQuestion.hints[j].id === searched) {
                                penalty = currentQuestion.hints[j].penalty;
                            }
                        }
                    }
                }
                return penalty;
            },
            getAnswerScore: function (searched, questions) {
                var nbQuestions = questions.length;
                var score = 0.0;
                for (var i = 0; i < nbQuestions; i++) {
                    var currentQuestion = questions[i];
                    // update exercise total points
                    for (var j = 0; j < currentQuestion.solutions.length; j++) {
                        if (currentQuestion.solutions[j].id === searched) {
                            score = currentQuestion.solutions[j].score;
                        }
                    }
                }
                return score;
            },
            // UTILS METHODS
            /**
             * get a sequence correction mode in a human readable word
             * @param {integer} mode
             * @returns {string} the humanized correction mode
             */
            getCorrectionMode: function (mode) {
                switch (mode) {
                    case "1":
                        return "test-end";
                        break;
                    case "2":
                        return "last-try";
                        break;
                    case "3":
                        return "after-date";
                        break;
                    case "4":
                        return "never";
                        break;
                    default:
                        return "never";
                }
            },
            /**
             * @param {object} object a javascript object with type property
             * @returns null or string
             */
            getObjectSimpleType: function (object) {
                if (!object.type || object.type === undefined || object.type === 'undefined') {
                    return null;
                } else {
                    var simpleType = null;
                    if (object.type === 'text/html') {
                        simpleType = 'html-text';
                    }
                    else if (object.type === 'text/plain') {
                        simpleType = 'simple-text';
                    }
                    else if (object.type === 'application/pdf' && object.url) {
                        simpleType = 'web-pdf';
                    }
                    else if ((object.type === 'image/png' || object.type === 'image/jpg' || object.type === 'image/jpeg') && object.url) {
                        simpleType = 'web-image';
                    }
                    else if ((object.type === 'image/png' || object.type === 'image/jpg' || object.type === 'image/jpeg') && object.encoding && object.data) {
                        simpleType = 'encoded-image';
                    }

                    return simpleType;
                }
            },
            /**
             * shuffle array elements
             * @param {array} the given array
             * @returns {array} the shuffled array
             */
            shuffleArray: function (array) {
                var currentIndex = array.length, temporaryValue, randomIndex;
                // While there remain elements to shuffle...
                while (0 !== currentIndex) {
                    // Pick a remaining element...
                    randomIndex = Math.floor(Math.random() * currentIndex);
                    currentIndex -= 1;

                    // And swap it with the current element.
                    temporaryValue = array[currentIndex];
                    array[currentIndex] = array[randomIndex];
                    array[randomIndex] = temporaryValue;
                }
                return array;
            }
        };
    }
]);



