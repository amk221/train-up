/* global jQuery */

jQuery(function($) {
  'use strict';

  /**
   * When searching for Groups that a user is in, we are left with
   * 'Search results: ""' on the page, and its ugly, so we remove it manually.
   */
  var $searchTitle = $('.wrap > h2 > .subtitle');
  var $breadcrumbs = $('.tu-breadcrumb-trail li');

  if ($breadcrumbs.length === 3) {
    $searchTitle.text('');
  }

  /**
   * The big ol' publish button and save draft button
   */
  var $save = $('#publish, #save-post');

  /**
   * The meta box container element
   */
  var $traineesBox = $('.tu-trainees-box');

  /**
   * The add a new trainee button
   */
  var $addTrainee = $traineesBox.find('.tu-add-trainee .button');

  /**
   * The remove a trainee button
   */
  var $removeTrainee = $traineesBox.find('.tu-remove-trainee .button');

  /**
   * The select box that lists the trainees for the current group
   */
  var $traineeIds = $traineesBox.find('select[name^=trainee_ids]');

  /**
   * This hidden input popuated with the trainee's ID when one is autocompleted
   */
  var $traineeId = $traineesBox.find('input[name=trainee_id]');

  /**
   * The autocompleter text element that allows for searching for a trainee.
   */
  var $traineeSearch = $traineesBox.find('input[data-name="trainee_id"]');

  /**
   * The text input that lets you choose a colour for a Group
   */
  var $colourPicker = $('.tu-group-colour');

  /**
   * - Callback fired when the add a trainee button is clicked
   * - Grab the ID from the hidden form input of the autocompleted trainee
   * - If they're not already in the trainee list for this group, then add them
   *   and clear the autocompleter.
   *
   * @param {object} e Click event
   */
  var addTrainee = function(e) {
    e.preventDefault();

    var traineeId = $traineeId.val();

    if (traineeId && !traineeInGroup(traineeId)) {
      insertTrainee();
      $traineeId.val('');
      $traineeSearch.val('');
    }
  };

  /**
   * @param {integer} Trainee ID
   * @return {boolean} Whether or not the given Trainee ID is alreadu listed
   * inside the select box of Trainees for the current Group.
   */
  var traineeInGroup = function(traineeId) {
    return !!$traineeIds.find('option[value="'+ traineeId +'"]').length;
  };

  /**
   * Move the autocompleted Trainee into the select box of Trainees.
   */
  var insertTrainee = function() {
    var $option = $('<option/>', {
      value: $traineeId.val(),
      text: $traineeSearch.val()
    });

    $traineeIds.append($option);
  };

  /**
   * - Callback fired when the remove trainees button is clicked
   * - Remove the selected option elements from the multiple select box.
   *
   * @param {object} e Click event
   */
  var removeTrainees = function(e) {
    e.preventDefault();

    $traineeIds.find(':selected').remove();
  };

  /**
   * Callback fired when the publish or save draft buttons are clicked
   * Select all the trainees in the box so that when the form is submitted
   * their values will be POSTed.
   */
  var selectTrainees = function() {
    $traineeIds.find('option').prop('selected', true);
  };

  /**
   * Add the DOM events that make the Group page work
   */
  $addTrainee.on('click.tu', addTrainee);
  $removeTrainee.on('click.tu', removeTrainees);
  $save.on('click.tu', selectTrainees);
  $colourPicker.wpColorPicker();

});