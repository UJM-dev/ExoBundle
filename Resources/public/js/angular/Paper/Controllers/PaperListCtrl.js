angular.module('Paper').controller('PaperListCtrl', [
    '$filter',
    'CommonService',
    'exercise',
    'PaperService',
    'papersPromise',
    function ($filter, CommonService, exercise, PaperService, papersPromise) {

        this.papers = papersPromise.papers;
        this.questions = papersPromise.questions;
        this.exercise = exercise;
        this.displayManualCorrectionMessage = false;

        // table data
        this.filtered = this.papers;
        this.query = '';
        this.showPagination = true;
        this.interrupted = false;

        // table config
        this.config = {
            itemsPerPage: 10,
            fillLastPage: false,
            paginatorLabels: {
                stepBack: '‹',
                stepAhead: '›',
                jumpBack: '«',
                jumpAhead: '»',
                first: Translator.trans('paper_list_table_first_page_label', {}, 'ujm_sequence'),
                last: Translator.trans('paper_list_table_last_page_label', {}, 'ujm_sequence')
            }

        };

        this.updateFilteredList = function () {
            this.filtered = $filter("filter")(this.papers, this.query);
        };

        this.togglePaginationButton = function () {
            this.showPagination = !this.showPagination;
            if (!this.showPagination) {
                this.config.itemsPerPage = this.papers.length;
            }
        };

        /**
         * Checks if we can display the correction link
         * For now the API does not return the needed data so...
         * @returns {bool}
         */
        this.checkCorrectionAvailability = function (paper) {
            var correctionMode = CommonService.getCorrectionMode(this.exercise.meta.correctionMode);
            // !!! countFinishedAttempts dont take the paper user into account...
            var nbFinishedAttempts = this.countFinishedAttempts();
            switch (correctionMode) {
                case "test-end":
                    return paper.end && paper.end !== undefined && paper.end !== '';
                    break;
                case "last-try":
                    // only used for normal user ? if admin i will always see correction link ?
                    return nbFinishedAttempts >= this.exercise.meta.maxAttempts;
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


        this.countFinishedAttempts = function () {
            var nb = 0;
            for (var i = 0; i < this.papers.length; i++) {
                if (this.papers[i].end && this.papers[i].end !== undefined && this.papers[i].end !== '') {
                    nb++;
                }
            }
            return nb;
        };

        this.needManualCorrection = function (){
            for(var i = 0; i < this.questions.length; i++){
                if(this.questions[i].typeOpen && this.questions[i].typeOpen === 'long'){
                    this.displayManualCorrectionMessage = true;
                    break;
                }
            }
        };

        /**
         * All data that need to be transformed and used in filter / sort
         * @returns {undefined}
         */
        this.setTableData = function () {
            for (var i = 0; i < this.filtered.length; i++) {
                // set scores in paper object and in the same time format end date
                if (this.filtered[i].end) { // TODO check score availability
                    this.filtered[i].score = CommonService.getPaperScore(this.filtered[i], this.questions) + '/20';
                }
                else {
                    this.filtered[i].end = '-';
                    this.filtered[i].score = '-';
                }
                // set interrupt property in a human readable way
                if (this.filtered[i].interrupted) {
                    this.filtered[i].interruptLabel = Translator.trans('paper_list_table_interrupted_yes', {}, 'ujm_sequence');
                    this.interrupted = true;
                } else {
                    this.filtered[i].interruptLabel = Translator.trans('paper_list_table_interrupted_no', {}, 'ujm_sequence');
                    this.interrupted = false;
                }
            }
        };

        /**
         * Delete all Papers of the Exercise
         */
        this.deletePapers = function deletePapers() {
            PaperService.deleteAll(this.papers);
        };

        this.setTableData();
        this.needManualCorrection();
    }
]);