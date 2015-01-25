/* jshint eqeqeq: false */
/* global jQuery, _, confirm, TU_QUESTIONS */

jQuery(function($) {
  'use strict';

  /**
   * The meta box that lets the user choose the type of Question.
   */
  var $typeBox = $('#type');

  /**
   * The single-answer meta box
   */
  var $singleBox = $('#single');

  /**
   * The multiple choice meta box
   */
  var $multipleBox = $('#multiple');

  /**
   * Radio buttons that let you choose the type of question
   */
  var $questionTypeChoices = $typeBox.find('input[name=question_type]');

  /**
   * The single answer text box that contains the actual answer.
   */
  var $singleAnswer = $singleBox.find('input[name=single_answer]');

  /**
   * The dropdown box that defines the comparison used to compute how the
   * single-answer is deemed to be correct.
   */
  var $comparisonChoice = $singleBox.find('select[name=single_answer_comparison]');

  /**
   * Element containing the answer checker
   */
  var $answerChecker = $singleBox.find('.tu-answer-checker');

  /**
   * The text field that specifies the pattern modifier for regex questions
   */
  var $patternModifier = $singleBox.find("input[name=pattern_modifier]");

  /**
   * Links that add another possible answer to a multiple choice question
   */
  var $addAnswerLinks = $('.tu-add-new-answer');

  /**
   * The input box that lets users check whether or not their answer is right.
   */
  var $checkerInput = $answerChecker.find('textarea');

  /**
   * The preview result as to whether the answer would be right or wrong.
   */
  var $checkerResult = $answerChecker.find('span');

  /**
   * Use underscores's templating to insert a new multiple choice answer.
   */
  var addNewAnswer = function() {
    var $box       = $(this).parents('.postbox');
    var $container = $box.find('.tu-answers tbody');
    var $answers   = $container.find('tr.tu-answer');
    var template   = $box.find('script.tu-answer-template').text();

    $container
      .append(_.template(template, { number: $answers.length }))
      .find(':input[type=text]:last').focus();

    return false;
  };

  /**
   * Go through given the meta box and find table cell elements that are
   * an 'index', and re-index them so that they are in order.
   *
   * @param {object} $metaBox
   */
  var reIndex = function($metaBox) {
    $metaBox.find('.tu-answers tbody td.tu-index').each(function(i) {
      $(this).text(i+1);
    });
  };

  /**
   * Callback fired when a remove answer link is clicked on a multiple choice
   * question. Find the row and remove it, the re-index the rows so they are
   * not out of sync.
   */
  var removeAnswer = function() {
    var $link = $(this);
    var $box  = $link.parents('.postbox');
    var $row  = $link.parents('tr');

    if (confirm(TU_QUESTIONS._confirmDeleteAnswer)) {
      $row.remove();
    }

    reIndex($box);

    return false;
  };

  /**
   * - Callback fired when one of the radio buttons is clicked that lets you
   *   choose what the question type should be.
   * - Hide all the question type meta boxes.
   * - Show the chosen meta box.
   * - Disable the form elements inside meta boxes that aren't in use so
   *   as to not screw up the POST data.
   */
  var changeQuestionType = function() {
    var chosenType = $(this).val();

    for (var type in TU_QUESTIONS.questionTypes) {
      $('#' + type)
        .hide()
        .find(':input').prop('disabled', true);
    }

    $('#' + chosenType)
      .show()
      .find(':input').prop('disabled', false);
  };

  /**
   * Hide the fields provided by WordPress that let a user choose the
   * parent post, we don't want Questions to be nested, this would be weird.
   */
  var hideHierarchyFields = function() {
    $('#pageparentdiv')
      .find('.inside p:contains("Parent"), #parent_id').hide();
  };

  /**
   * - Fired when the question comparison type dropdown box changes.
   * - Hide the pattern modifier box if the comparison type is not regex
   * - Re-check the answer now that the comparison has changed.
   */
  var changeComparison = function() {
    var c = $comparisonChoice.val();
    $patternModifier[(c === "matches-pattern" ? "show" : "hide")]();
    checkAnswer();
  };

  /**
   * Try to mimic the serverside checking of Questions::validate_answer
   * as closely as possible, so that the user can 'preview' their answer.
   */
  var checkAnswer = function() {
    var comparison    = $comparisonChoice.val();
    var error         = false;
    var correct       = false;
    var usersAnswer   = $checkerInput.val();
    var correctAnswer = $singleAnswer.val();

    if (comparison === 'equal-to') {
      correct = usersAnswer == correctAnswer;
    }
    else if (comparison === 'greater-than') {
      correct = parseFloat(usersAnswer) > parseFloat(correctAnswer);
    }
    else if (comparison === 'greater-than-or-equal-to') {
      correct = parseFloat(usersAnswer) >= parseFloat(correctAnswer);
    }
    else if (comparison === 'less-than') {
      correct = parseFloat(usersAnswer) < parseFloat(correctAnswer);
    }
    else if (comparison === 'less-than-or-equal-to') {
      correct = parseFloat(usersAnswer) <= parseFloat(correctAnswer);
    }
    else if (comparison === 'contains') {
      var contains = new RegExp(correctAnswer, 'i');
      correct = contains.test(usersAnswer);
    }
    else if (comparison === 'between') {
      var parts = correctAnswer.match(/(\d+)[^\d]+(\d+)/);
      if (parts && parts.length === 3) {
        var lower = parts[1];
        var upper = parts[2];

        correct = (
          parseFloat(usersAnswer) >= parseFloat(lower) &&
          parseFloat(usersAnswer) <= parseFloat(upper)
        );
      } else {
        error = true;
      }
    }
    else if (comparison === 'matches-pattern') {
      try {
        var modifier = $patternModifier.val();
        var pattern = new RegExp(correctAnswer, modifier);
        correct = pattern.test(usersAnswer);
      } catch(e) {
        error = true;
      }
    }

    var str = correct ? 'correct' : 'incorrect';
    $checkerResult
      .text(error ? TU_QUESTIONS._error : str)
      .removeClass('correct incorrect')
      .addClass(str);
  };

  /**
   * When add answer links are clicked, insert a new multiple choice answer row.
   */
  $addAnswerLinks.on('click.tu', addNewAnswer);

  /**
   * Live click event to allow removing of multiple choice answer rows.
   */
  $('#poststuff').on('click.tu', 'td.tu-answer-remove a', removeAnswer);

  /**
   * When the single answer text input changes, re-check the answer
   */
  $singleAnswer.on('keyup.tu', checkAnswer);

  /**
   * When the checker input changes, re-check the answer
   */
  $checkerInput.on('keyup.tu', checkAnswer);

  /**
   * When the pattern modigier changes, re-check the answer
   */
  $patternModifier.on('keyup.tu', checkAnswer);

  /**
   * When the comparison choice is change, re-check the answer
   */
  $comparisonChoice.on('change.tu', changeComparison);

  /**
   * When the question type radio buttons are clicked, change the type
   * Also, always click the checked box initially to trigger showing of the
   * correct box on load.
   */
  $questionTypeChoices
    .on('click.tu', changeQuestionType)
    .filter(':checked')
    .trigger('click.tu');

  /**
   * Hide WordPress' hierarchy fields
   */
  hideHierarchyFields();

  /**
   * Trigger changing of the comparison box, so the modifier box is shown
   * or hidden on initial load, depending on the question comparison type.
   */
  changeComparison();

  /**
   * Expose the re-index function so that add-on plugins can utilize it.
   */
  TU_QUESTIONS.reIndexMultipleAnswers = reIndex;

});