export default class QuestionService {

  static get $inject(){ return ['$http', '$filter', '$q', '$window', 'PlayerDataSharing']; }

  constructor($http, $filter, $q, $window, PlayerDataSharing){
    this.$q = $q;
    this.$http = $http;
    this.$filter = $filter;
    this.$window = $window;
    this.PlayerDataSharing = PlayerDataSharing;
  }

  /**
   * Get an hint
   * @returns promise
   */
  getHint (hid) {
      var deferred = this.$q.defer();
      var paper = this.PlayerDataSharing.getPaper();
      var self = this;
      this.$http
              .get(
                      Routing.generate('exercice_hint', {paperId: paper.id, hintId: hid})
                      )
              .success(function (response) {
                  deferred.resolve(response);
              })
              .error(function (data, status) {
                  deferred.reject([]);
                  var msg = data && data.error && data.error.message ? data.error.message : 'QuestionService get hint error';
                  var code = data && data.error && data.error.code ? data.error.code : 400;
                  var url = Routing.generate('ujm_sequence_error', {message:msg, code:code});

                  this.$window.location = url;
              }.bind(this));

      return deferred.promise;
  }
  /**
   * Get a hint penalty
   * @param {type} collection array of penalty
   * @param {type} searched searched id
   * @returns number
   */
  getHintPenalty (collection, searched) {
      for (var i = 0; i < collection.length; i++) {
          if (collection[i].id === searched) {
              return collection[i].penalty;
          }
      }
  }
  /**
   * Used for displaying in-context question feedback and solutions
   * @param {type} id question id
   * @returns {$q@call;defer.promise}
   */
  getQuestionSolutions(id){
      var deferred = this.$q.defer();
      this.$http
              .get(
                  Routing.generate('get_question_solutions', {id: id})
              )
              .success(function (response) {
                  deferred.resolve(response);
              })
              .error(function (data, status) {
                  deferred.reject([]);
                  var msg = data && data.error && data.error.message ? data.error.message : 'QuestionService get solutions error';
                  var code = data && data.error && data.error.code ? data.error.code : 400;
                  var url = Routing.generate('ujm_sequence_error', {message:msg, code:code});
                  this.$window.location = url;
              }.bind(this));

      return deferred.promise;
  }
}
