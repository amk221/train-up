/* global jQuery, TU_TESTS, confirm */

jQuery(function($) {
  'use strict';

  /**
   * WordPress elements that when clicked, delete a Test
   */
  var $deleteTestLinks = $('.type-tu_test .delete .submitdelete');

  /**
   * Elements that when clicked, delete a Question
   */
  var $deleteQuestionLinks = $('td.tu-question-remove .tu-remove');

  /**
   * Elements that when clicked, reset a Test
   */
  var $resetTestLinks = $('.tu-reset-test');

  /**
   * Callback fired when a WordPress link is clicked that is to delete a Test,
   * check with the user first, because the Test's associated Questions will
   * also be deleted.
   */
  var confirmDeleteTest = function() {
    return confirm(TU_TESTS._confirmDeleteTest);
  };

  /**
   * Callback fired when a Question is to be deleted, check with the user first.
   */
  var confirmDeleteQuestion = function() {
    return confirm(TU_TESTS._confirmDeleteQuestion);
  };

  /**
   * Callback fired when a Test is about to be Reset, check with the user first.
   */
  var confirmResetTest = function() {
    return confirm(TU_TESTS._confirmResetTest);
  };

  /**
   * Add events that prompt the user before doing a destructive action.
   */
  $deleteTestLinks.on('click.tu', confirmDeleteTest);
  $deleteQuestionLinks.on('click.tu', confirmDeleteQuestion);
  $resetTestLinks.on('click.tu', confirmResetTest);

});

