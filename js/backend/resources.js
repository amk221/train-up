/* global jQuery, TU_RESOURCES */

jQuery(function($) {
  'use strict';

  /**
   * The button that when pressed, adds a new schedule
   */
  var $addScheduleButton = $('.tu-add-schdule-button');

  /**
   * The list of groups available to give a schedule
   */
  var $scheduleGroups = $('.tu-add-schedule-groups');

  /**
   * The datetime field that allows you to specify when to schedule the resource
   */
  var $scheduleDateTime = $('.tu-add-schedule-datetime');

  /**
   * Callback fired when a remove schedule link is clicked, find the row and
   * remove it.
   */
  var removeSchedule = function(e) {
    e.preventDefault();

    var $link = $(this);
    var $box  = $link.parents('.postbox');
    var $row  = $link.parents('tr');

    if (confirm(TU_RESOURCES._confirmDeleteSchedule)) {
      $row.remove();
    }

    handleDisabled();
  };

  /**
   * Use underscores's templating to insert a new schedule.
   */
  var addNewSchedule = function(e) {
    e.preventDefault();

    var $box         = $(this).parents('.postbox');
    var $container   = $box.find('.tu-schedules tbody');
    var $schedules   = $container.find('tr.tu-schedule');
    var $allSchedule = $box.find('input[name="tu_schedule[all]"]');
    var template     = $box.find('script.tu-schedule-template').text();
    var groupID      = $scheduleGroups.val();
    var dateTime     = $scheduleDateTime.val();
    var date         = new Date(dateTime);
    var dateStr      = date.toDateString();
    var timeStr      = date.toLocaleTimeString({ hour12: true }).toLowerCase();
    var dateTimeStr  = dateStr + ' @ ' + timeStr;

    if ($scheduleGroups.children('option:selected').is(':disabled')) {
      alert(TU_RESOURCES._scheduleAlreadyExists);
      return false;
    } else if (isNaN(date.getHours())) {
      alert(TU_RESOURCES._invalidScheduleTime);
      return false;
    } else if ($allSchedule.length) {
      alert(TU_RESOURCES._scheduleAffectingAllGroups);
      return false;
    } else if (groupID === 'all' && $schedules.length > 0) {
      alert(TU_RESOURCES._allScheduleNotAllowed);
      return false;
    }

    $container.append(_.template(template, {
      number:      $schedules.length,
      groupName:   $scheduleGroups.children('option:selected').text(),
      groupID:     groupID,
      dateTime:    dateTime,
      dateTimeStr: dateTimeStr
    }));

    handleDisabled();
  };

  /**
   * 
   */
  var handleDisabled = function() {
    $scheduleGroups
      .children('option')
      .prop('disabled', false)
      .each(function(i, option) {
        var $option = $(option);
        var groupID = $option.val();
        if ($('.tu-schedules input[name="tu_schedule['+ groupID +']"]').length) {
          $option.prop('disabled', true);
        }
      });
  };

  /**
   * Live click event to allow removing of multiple choice answer rows.
   */
  $('#poststuff').on('click.tu', 'td.tu-schedule-action a.tu-remove', removeSchedule);

  /**
   * Live click event to allow adding of a new schedule
   */
  $addScheduleButton.on('click.tu', addNewSchedule);

  /**
   * Grey out used schedule-groups straight away
   */
  handleDisabled();
});