export default class OpenQuestionCtrl {

  constructor($ngBootbox, $scope, CommonService, QuestionService, PlayerDataSharing){
    this.question = {};
    this.currentQuestionPaperData = {};
    this.usedHints = [];
    this.answer = "";

    // instant feedback data
    this.canSeeFeedback = false;
    this.feedbackIsVisible = false;
    this.solutions = {};
    this.questionFeedback = '';

    this.bootbox = $ngBootbox;
    this.scope = $scope;
    this.CommonService = CommonService;
    this.QuestionService = QuestionService;
    this.playerDataSharing = PlayerDataSharing;

    /**
     * Listen to show-feedback event (broadcasted by ExercisePlayerCtrl)
     */
    this.scope.$on('show-feedback', function (event, data) {
        this.showFeedback();
    });
  }

  init (question, canSeeFeedback) {
      // those data are updated by view and sent to common service as soon as they change
      this.currentQuestionPaperData = this.playerDataSharing.setCurrentQuestionPaperData(question);
      this.question = question;
      this.canSeeFeedback = canSeeFeedback;
      // init student data question object
      this.playerDataSharing.setStudentData(question);

      if (this.currentQuestionPaperData.hints && this.currentQuestionPaperData.hints.length > 0) {
          // init used hints display
          for (var i = 0; i < this.currentQuestionPaperData.hints.length; i++) {
              this.getHintData(this.currentQuestionPaperData.hints[i]);
          }
      }

      if (typeof this.currentQuestionPaperData.answer !== "string" && this.currentQuestionPaperData.answer.length === 0) {
          this.answer = "";
          this.currentQuestionPaperData.answer = this.answer;
          this.playerDataSharing.setStudentData(question, this.currentQuestionPaperData);
      }

      this.answer = this.currentQuestionPaperData.answer;
  };

  /**
   * check if a Hint has already been used (in paper)
   * @param {type} id
   * @returns {Boolean}
   */
  hintIsUsed (id) {
      if (this.currentQuestionPaperData && this.currentQuestionPaperData.hints) {
          for (var i = 0; i < this.currentQuestionPaperData.hints.length; i++) {
              if (this.currentQuestionPaperData.hints[i] === id) {
                  return true;
              }
          }
      }
      return false;
  };

  /**
   * Get hint data and update student data in common service
   * @param {type} hintId
   * @returns {undefined}
   */
  showHint (id) {
      var penalty = this.QuestionService.getHintPenalty(this.question.hints, id);
      this.bootbox.confirm(Translator.trans('question_show_hint_confirm', {1: penalty}, 'ujm_sequence'))
              .then(function () {
                  this.getHintData(id);
                  this.currentQuestionPaperData.hints.push(id);
                  this.updateStudentData();
                  // hide hint button
                  angular.element('#hint-' + id).hide();
              }.bind(this));
  };

  getHintData (id) {
      var promise = this.QuestionService.getHint(id);
      promise.then(function (result) {
          this.usedHints.push(result);

      }.bind(this));
  };

  /**
   * Called on each checkbox / radiobutton click
   * We need to share those informations with parent controllers
   * For that purpose we use a shared service
   */
  updateStudentData () {
      // save the answer in currentQuestionPaperData, tu be able to reuse it during the sequence
      this.currentQuestionPaperData.answer = this.answer;
      this.playerDataSharing.setStudentData(this.question, this.currentQuestionPaperData);
  };

  showFeedback () {
      // get question answers and feedback ONLY IF NEEDED
      var promise = this.QuestionService.getQuestionSolutions(this.question.id);
      promise.then(function (result) {
          this.feedbackIsVisible = true;
          this.solutions = result.solutions;
          this.questionFeedback = result.feedback;
      }.bind(this));
  };



  /**
   * Hide / show a specific panel content and handle hide / show button icon
   * @param {string} id (part of the panel id)
   */
  toggleDetails (id) {

      // custom toggle function to avoid the use of jquery
      if (angular.element('#question-body-' + id).attr('style') === undefined) {
          angular.element('#question-body-' + id).attr('style', 'display: none;');
      } else {
          // hide / show panel body
          if (angular.element('#question-body-' + id).attr('style') === 'display: none;') {
              angular.element('#question-body-' + id).attr('style', 'display: block;');
          } else if (angular.element('#question-body-' + id).attr('style') === 'display: block;') {
              angular.element('#question-body-' + id).attr('style', 'display: none;');
          }
      }

      // handle hide / show button icon
      if (angular.element('#question-toggle-' + id).hasClass('fa-chevron-down')) {
          angular.element('#question-toggle-' + id).removeClass('fa-chevron-down').addClass('fa-chevron-right');
      }
      else if (angular.element('#question-toggle-' + id).hasClass('fa-chevron-right')) {
          angular.element('#question-toggle-' + id).removeClass('fa-chevron-right').addClass('fa-chevron-down');
      }
  };
}
