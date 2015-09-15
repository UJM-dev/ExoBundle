(function () {
    'use strict';

    // exercise player module
    angular.module('SequencePlayerApp', [
        'ngSanitize',
        'ui.bootstrap',
        'ui.tinymce',
        'ui.translation',
        'ui.resourcePicker',
        'ngBootbox',
        'Common',
        'Sequence'
    ])
    .filter(
    'unsafe', 
    function($sce) { 
        return $sce.trustAsHtml; 
    });
})();