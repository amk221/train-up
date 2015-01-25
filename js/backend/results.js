/* jshint eqeqeq: false */
/* global jQuery, google, document, ajaxurl, TU, TU_RESULTS, confirm */

(function() {
  'use strict';

  /**
   * Null to start with, but will be jQuery eventually
   */
  var $;

  /**
   * The user performance container element for the graph
   */
  var $userPerformance;

  /**
   * The group performance container element for the graph
   */
  var $groupPerformance;

  /**
   * Elements that when clicked toggle the visibility of a Test's parents.
   */
  var $toggleNestingLink;

  /**
   * The large or small results table, which ever is on the page.
   */
  var $resultsTable;

  /**
   * The element that lets administrators manually set a Result percentage
   * and its temporary input element.
   */
  var $resultPercentage, $resultGrade, $resultPassed;

  /**
   * The temporary input element that allows administrators to set a
   * manual percentage.
   */
  var $manualPercentage = {};

  /**
   * The links that allow admins to delete files that have been uploaded as
   * answers to Questions, but for old resits attempts.
   */
  var $deleteFiles;

  /**
   * - Callback fired when Google's visualization API is ready.
   * - When the page is ready, we can continue...
   */
  var init = function() {
    jQuery(ready);
  };

  /**
   * - Fired on document ready when Google visualization is also ready.
   * - Now that the document is ready, set up the dollar alias
   *   and load all the DOM elements required.
   * - Add the necessary events and render the graphs.
   * 
   * @param {function} jQuery
   */
  var ready = function(jQ) {
    $ = jQ;

    $('#title').attr('readonly', 'readonly');

    $userPerformance   = $('#tu-user-performance');
    $groupPerformance  = $('#tu-group-performance');
    $toggleNestingLink = $('.tu-toggle-nesting');
    $resultsTable      = $('.tu-results');
    $resultPercentage  = $('.tu-result-percentage');
    $resultGrade       = $('.tu-result-grade');
    $resultPassed      = $('.tu-result-passed');
    $deleteFiles       = $('.tu-delete-files');

    $toggleNestingLink.on('click.tu', toggleNesting);
    $resultPercentage.on('click.tu', makePercentageEditable);
    $deleteFiles.on('click.tu', checkDelete);
    $(document).on('click.tu', saveManualPercentage);

    userPerformance();
    groupPerformance();
  };

  /**
   * - Callback fired when a link is clicked that is to delete redundant.
   *   uploaded files
   * - Double check with the administrator first.
   */
  var checkDelete = function() {
    return confirm(TU_RESULTS._confirmDeleteFiles);
  };

  /**
   * - Callback for when the table cell is clicked that contains the Trainee's
   *   percentage for a test.
   * - Temporarily insert an input box in its place so the administrator
   *   can manually set a percentage.
   */
  var makePercentageEditable = function(e) {
    var $span      = $resultPercentage.children('span');
    var percentage = $span.text();

    $manualPercentage = $('<input/>', {
      val: percentage,
      name: 'tu_manual_percentage',
      size: 3,
      type: 'number',
      min: 0,
      max: 100,
      step: 1
    });

    $resultsTable.addClass('tu-results-editing');
    $resultPercentage.off('click.tu');
    $span.html($manualPercentage);

    $manualPercentage
      .focus()
      .on('keypress.tu', saveManualPercentage);
  };

  /**
   * - Fired when an administrator manually sets a mark for a Trainees test
   *   result
   * - Do the saving when they press enter, or click outside of the box
   *   and we actually editing the value in the first place.
   * - Remove the temporary text input and replace it with the new percentage.
   * - Fire off a request to the API to re-determine the grade.
   */
  var saveManualPercentage = function(e) {
    var clickedOutside = (
      e.type === 'click' &&
      e.target != $resultPercentage.get(0) &&
      e.target != $resultPercentage.children('span').get(0) &&
      ($manualPercentage.length && e.target != $manualPercentage.get(0))
    );

    var pressedEnter = (e.type === 'keypress' && e.which === 13);
    var wasEditing   = !!$resultPercentage.find('input').length;
    var save         = (pressedEnter || clickedOutside) && wasEditing;

    if (!save) return;

    var $span      = $resultPercentage.children('span');
    var percentage = parseFloat($manualPercentage.val() || 0);

    if (percentage > 100) percentage = 100;
    if (percentage < 0) percentage = 0;

    $resultsTable.removeClass('tu-results-editing');
    $resultPercentage.on('click.tu', makePercentageEditable);
    $span.text(percentage);

    updateResult(percentage);
  };

  /**
   * Update the Result in the archive with the new percentage.
   *
   * @param {integer} percentage
   */
  var updateResult = function(percentage) {
    $resultsTable.addClass('tu-results-loading');

    $.ajax({
      type: 'POST',
      url: ajaxurl,
      beforeSend: TU.loading.start,
      complete: TU.loading.stop,
      success: refreshView,
      dataType: 'json',
      data: {
        tu_func: 'results:set_manual_percentage',
        tu_args: [TU.activePostId, percentage]
      }
    });
  };

  /**
   * - Callback fired when the API has successfully update the active Test
   *   Result's percentage with a manual value.
   * - The API returns with the associated Grade for the given percentage.
   * - Update the view.
   */
  var refreshView = function(data) {
    var passed    = $resultPassed.data(data.passed > 0 ? 'yes' : 'no');
    var className = 'tu-passed-' + data.passed;

    $resultGrade.text(data.grade);

    $resultPassed
      .children('span')
      .text(passed)
      .attr('class', className);

    $resultsTable.removeClass('tu-results-loading');
  };

  /**
   * Callback fired when an element is clicked that toggles the visibility 
   * of a column in the archive table that shows a Test's parents. It is hidden
   * by default, because it will be too long to fit on screen generally.
   */
  var toggleNesting = function() {
    $(this).parents('table').find('td.tu-nesting span').toggle();
  };

  /**
   * If the user performance element is available, then render a line graph
   */
  var userPerformance = function() {
    if (!$userPerformance.length) return;

    var data = google.visualization.arrayToDataTable(TU_RESULTS.userPerformance);

    var options = {
      width: 300,
      height: 50,
      chartArea: {
        top: 0,
        left: 0,
        height: '100%',
        width: '100%'
      },
      colors: ['#ccc'],
      backgroundColor: "transparent",
      hAxis: {
        textColor: 'transparent'
      },
      vAxis: {
        minValue: 0,
        maxValue: 100,
        textColor: 'transparent',
        baselineColor: 'transparent',
        gridlineColor: 'transparent'
      }
    };

    var chart = new google.visualization.AreaChart($userPerformance.get(0));
    chart.draw(data, options);
  };

  /**
   * If the group performance element is available then render a bar chart.
   * Unfortunatly, to we can't use simply use arrayToDataTable like usual,
   * because we need to assign colours to each column.
   *
   * @see http://goo.gl/lPQci
   */
  var groupPerformance = function() {
    if (!$groupPerformance.length) return;

    var data    = new google.visualization.DataTable();
    var p       = TU_RESULTS.groupPerformance;
    var headers = p.shift();
    var colours = [];

    data.addColumn('string', headers[0]);
    data.addRows(1);
    data.setValue(0, 0, headers[2]);

    for (var i = 0; i < p.length; ++i) {
      data.addColumn('number', p[i][0]);
      data.setValue(0, i + 1, p[i][2]);
      colours.push(p[i][3]);
    }

    var options = {
      width: 300,
      height: 50,
      chartArea: {
        top: 0,
        left: 0,
        height: '100%',
        width: '100%'
      },
      fontSize: 12,
      vAxis: {
        minValue: 0,
        maxValue: 100,
        gridlineColor: 'transparent',
        baselineColor: 'transparent'
      },
      colors: colours,
      backgroundColor: "transparent",
      hAxis: {
        textColor: 'transparent'
      },
      bar: { groupWidth: '98%' },
      tooltip: {
        isHtml: true,
        trigger: 'selection'
      }
    };

    var chart = new google.visualization.ColumnChart($groupPerformance.get(0));

    chart.setAction({
      id: 'groupFilter',
      text: TU_RESULTS._filterResults,
      action: function() {
        var row      = chart.getSelection()[0].row;
        var col      = chart.getSelection()[0].column;
        var groupID  = TU_RESULTS.groupPerformance[col-1][1];
        var oldGroup = /tu_group_id=\d+/;
        var newGroup = 'tu_group_id=' + groupID
        var url      = window.location.href;

        url = oldGroup.test(url)
          ? url.replace(oldGroup, newGroup)
          : url + '&' + newGroup

        window.location.href = url;
      }
    });

    chart.draw(data, options);
  };

  /**
   * Load Google's visualisation API
   */
  google.load('visualization', '1.0', {'packages':['corechart']});
  google.setOnLoadCallback(init);

}());
