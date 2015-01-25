/* global jQuery, TU, TU_TESTS, setInterval, clearInterval, alert, window, confirm */

jQuery(function($) {
  'use strict';

  /**
   * Link elements that submit a Trainee's answers.
   */
  var $finishTestLinks = $('.tu-finish-test-link');

  /**
   * The countdown timer
   */
  var countdown;

  /**
   * The DOM elements that auto update with the countdown timer value.
   */
  var $timeRemaining = $('.tu-time-remaining');

  /**
   * Get now in a unix timestamp format
   * (divide by 1000 because JS's time is in milliseconds and PHP's is seconds).
   *
   * @return {integer}
   */
  var now = function() {
    return Math.floor((new Date()).getTime() / 1000);
  };

  /**
   * - Compare the given timestamp with now, to find the difference then
   *   get the datetime parts.
   * - The hash returned should be as similar as possible to the PHP formatting
   *   for a DateInterval object: http://php.net/manual/dateinterval.format.php
   *
   * @param {integer} end Unix timestamp
   * @return {object} Hash of information about the time remaining
   */
  var getTimeRemaining = function(end) {
    var diff    = end - now();
    var seconds = (diff < 0) ? 0 : diff;

    var days = Math.floor(seconds / 86400);
    seconds %= 86400;
    var hours = Math.floor(seconds / 3600);
    seconds %= 3600;
    var minutes = Math.floor(seconds / 60);
    seconds %= 60;

    return {
      d: days,
      h: hours,
      i: minutes,
      s: seconds
    };
  };

  /**
   * Callback in the context of a DOM element, update its inner text to be
   * the countdown timer using the element's data-format attribute for
   * the base.
   *
   * @param {object} The date parts to update the time with.
   */
  var updateTimeRemaining = function(parts) {
    var $remaining = $(this);
    var remaining  = $remaining.data('format');

    for (var part in parts) {
      remaining = remaining.replace(new RegExp('%'+ part), parts[part]);
    }

    $remaining.text(remaining);
  };

  /**
   * - Callback for a setTimeout fired every second
   * - Find out the time remaining, and update all the countdown elements.
   * - If the time has run out, redirect to the submit results page.
   */
  var handleTimeRemaining = function() {
    $timeRemaining.each(
      updateTimeRemaining,
      [getTimeRemaining(TU_TESTS.endTime)]
    );

    if (TU_TESTS.endTime - now() < 0) {
      alert(TU_TESTS._timeUp);
      window.location.href = TU_TESTS.finishTestUrl;
      clearInterval(countdown);
    }
  };

  /**
   * - Callback fired when a Trainee clicks a link that will submit their
   *   answers and generate the Test results.
   * - Double check with them first with a native confirm box
   */
  var checkFinishTest = function(e) {
    if (!confirm(TU_TESTS._finishTestCheck)) {
      e.preventDefault();
    }
  };

  /**
   * If the active test has a time limit, and it has been started by the user
   * then start the countdown timers!
   */
  if (TU_TESTS.timeLimit !== '00:00:00' && TU_TESTS.startTime) {
    countdown = setInterval(handleTimeRemaining, 1000);
  }

  /**
   * Confirm with the Trainee before their answers are submitted
   */
  $finishTestLinks.on('click.tu', checkFinishTest);

});
