/* global TU_GRADES, _, jQuery */

jQuery(function($) {
  'use strict';

  /**
   * The link to dynamically create a new set of grade elements
   */
  var $addGradeLink = $('.tu-add-new-grade');

  /**
   * The container element for the grades
   */
  var $gradesContainer = $('.tu-grades tbody');

  /**
   * The fail input is always the first grade
   */
  var $failInput = $('td.tu-grade-description:eq(0) input');

  /**
   * The pass input is always the second grade
   */
  var $passInput = $('td.tu-grade-description:eq(1) input');

  /**
   * - Callback fired when the add a new grade button is clicked
   * - Use underscore's templating to dynamically generate a new grade row
   *   to append to the grade container.
   *
   * @return {boolean}
   */
  var addNewGrade = function() {
    $gradesContainer.append(_.template($('#grade_template').text(), {
      i: getNumberOfGrades(),
      percentage: getNextPercentage()
    }));
    return false;
  };

  /**
   * - Callback fired when the remove grade button is clicked.
   * - Find the row that the hyperlink clicked is within, then remove it.
   *
   * @return {boolean}
   */
  var removeGrade = function() {
    $(this).parents('tr:first').remove();
    return false;
  };

  /**
   * - Find the last grade in the table and get its percentage
   * - The next grade is assumed to be 10 percent more
   *
   * @return {integer} The next percentage
   */
  var getNextPercentage = function() {
    var percentage = Number($('.tu-grade-percentage:last input').val()) + 10;
    percentage = percentage >= 100 ? 100 : percentage;
    return percentage;
  };

  /**
   * Return the amount of grades currently listed
   */
  var getNumberOfGrades = function() {
    return $('.tu-grade').length;
  };

  /**
   * Always restore a fail grade if one isn't provided
   */
  var restoreFail = function() {
    if (!$(this).val()) {
      $(this).val(TU_GRADES._fail);
    }
  };

  /**
   * Always restore a pass grade if one isn't provided
   */
  var restorePass = function() {
    if (!$(this).val()) {
      $(this).val(TU_GRADES._pass);
    }
  };

  /**
   * Add the events that make it all work
   */
  $addGradeLink.on('click.tu', addNewGrade);
  $gradesContainer.on('click.tu', '.tu-grade-remove a', removeGrade);
  $failInput.on('blur.tu', restoreFail);
  $passInput.on('blur.tu', restorePass);

});