export default class ExercisePlayerCtrl {

  //constructor($window, $scope, ExerciseService, CommonService, PlayerDataSharing) {
  constructor($window, $scope, PlayerDataSharing) {
    this.exercise = {};
    this.paper = {};
    this.user = {};

    this.isFinished = false;
    this.isLastStep = false;
    this.isFirstStep = true;
    this.feedbackIsShown = false;
    this.currentStepIndex = 0;
    this.playerDataSharing = PlayerDataSharing;

    this.window = $window;
    this.scope = $scope;

  }

  // init directive with appropriate data
  init(paper, exercise, user, currentStepIndex) {
    console.log('init');
    this.exercise = this.playerDataSharing.setExercise(exercise);
    this.paper = this.playerDataSharing.setPaper(paper);
    this.user = this.playerDataSharing.setUser(user);
    this.currentStepIndex = currentStepIndex;

    console.log(this.user);
    this.setCurrentStep(this.currentStepIndex);
  };

  /**
   * Check index data validity and set current step
   * @param {Number} index
   */
  setCurrentStep (index) {
    this.isFirstStep = index === 0;
    this.isLastStep = index === this.exercise.steps.length - 1;
    // check new index is in computable range
    if (index < this.exercise.steps.length && index >= 0) {
      this.currentStep = this.exercise.steps[index];
    } else {
      var url = Routing.generate('ujm_sequence_error', {
        message: 'index out of bounds',
        code: '400'
      });
      this.window.location = url;
    }
  };

  /**
   * Get the step number for display
   * @returns {Number}
   */
  getCurrentStepNumber () {
    return this.currentStepIndex + 1;
  };

  /**
   * When using the drop down to jump to a specific step
   * @param {Object} step
   */
  jumpToStep (step) {
    if (this.exercise.steps.indexOf(step) !== this.exercise.steps.indexOf(this.currentStep)) {
      this.validateStep('goto', this.exercise.steps.indexOf(step));
    }
  };

  /**
   * save the current step in paper js object
   * in some case end the exercise
   * go to another step or end exercise
   * @param {String} action
   * @param {Number} index (nullable) the step index when using direct access
   */
  validateStep (action, index) {

    // manualy disable tooltips...
    $('.tooltip').each(function() {
      $(this).hide();
    });

    // get next step index
    this.currentStepIndex = this.getNextStepIndex(this.currentStepIndex, action, index);

    // data set by question directive
    var studentData = PlayerDataSharing.getStudentData();
    // save the given answer (even if empty !)
    var submitPromise = ExerciseService.submitAnswer(this.paper.id, studentData);
    submitPromise.then(function(result) {
      // then navigate to desired step / end / terminate exercise
      this.handleStepNavigation(action, studentData.paper);

    }.bind(this));
  };

  /**
   *
   * @param {number} current current index
   * @param {string} action
   * @param {number} index the index to reach (when the drop box is used)
   * @returns {number}
   */
  getNextStepIndex (current, action, index) {
    var newIndex = 0;
    if (action && (action === 'forward' || action === 'backward')) {
      newIndex = action === 'forward' ? current + 1 : current - 1;
    } else if (action && action === 'goto' && index !== undefined) {
      newIndex = index;
    }
    return newIndex;
  };

  /**
   * Navigate to desired step or end exercise and redirect to appropriate view
   * @param {string} action
   * @param {object} paper
   */
  handleStepNavigation (action, paper) {
    this.feedbackIsShown = false;
    if (action && (action === 'forward' || action === 'backward' || action === 'goto')) {
      this.setCurrentStep(this.currentStepIndex);
    } else if (action && action === 'end') {
      var endPromise = ExerciseService.endSequence(paper);
      endPromise.then(function(result) {
        if (this.checkCorrectionAvailability()) {
          // go to paper correction view
          var url = CommonService.generateUrl('paper-list', this.exercise.id) + '#/' + this.exercise.id + '/' + paper.id;
          this.window.location = url;
        } else {
          // go to exercise home page
          var url = CommonService.generateUrl('exercise-home', this.exercise.id);
          this.window.location = url;
        }
      }.bind(this));
    } else if (action && action === 'interrupt') {
      // go to exercise home page
      var url = CommonService.generateUrl('exercise-home', this.exercise.id);
      this.window.location = url;
    } else {
      var url = Routing.generate('ujm_sequence_error', {
        message: 'action not allowed',
        code: '400'
      });
      this.window.location = url;
    }
  };


  /**
   * Check if correction is available for an exercise
   * @returns {Boolean}
   */
  checkCorrectionAvailability () {
    var correctionMode = CommonService.getCorrectionMode(this.exercise.meta.correctionMode);
    switch (correctionMode) {
      case "test-end":
        return true;
        break;
      case "last-try":
        // check if current try is the last one ?
        return this.paper.number === this.exercise.meta.maxAttempts;
        break;
      case "after-date":
        var now = new Date();
        var searched = new RegExp('-', 'g');
        var correctionDate = new Date(Date.parse(this.exercise.meta.correctionDate.replace(searched, '/')));
        return now >= correctionDate;
        break;
      case "never":
        return false;
        break;
      default:
        return false;
    }

  };

  showFeedback () {
    this.feedbackIsShown = true;
    this.scope.$broadcast('show-feedback');
  };

  /**
   * Checks if feedback fields can be visible at some times
   * @returns {Boolean}
   */
  checkIfFeedbackIsAvailable () {
    //return this.exercise.meta.exerciseType === 'formatif';
    return false;
  };
}
