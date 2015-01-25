/* global jQuery, TU_IMPORTER, ajaxurl, FormData, window, alert */

jQuery(function ($) {
  'use strict';

  /**
   * The importer container element
   */
  var $importer = $('.tu-importer-dialog');

  /**
   * Links that trigger showing of the imported dialog
   */
  var $importerLinks = $('.tu-importer-link');

  /**
   * The hidden form input that specified which Test the Questions belong to
   */
  var $testID = $('#tu-importer-test-id');

  /**
   * The file importer form element
   */
  var $importFile = $('#tu-import-file');

  /**
   * The progress bar element
   */
  var $progressBar = $('.tu-import-progress-bar');

  /**
   * The element that houses the result of the import from the API.
   */
  var $importResult = $('.tu-import-result');

  /**
   * - Callback fired when the importer is launched
   * - Turn the importer element into a dialog and open it
   * - Set the buttons to be pre-upload buttons
   * - Re-enable the file browser element
   * - Re-set the progress bar to zero
   * - Clear any previous result from the importer
   *
   * @return {object} e Click event
   */
  var open = function(e) {
    e.preventDefault();
    $importer.dialog('open');
    $importer.dialog('option', { buttons: preButtons });
    $importFile.prop('disabled', false);
    $progressBar.val(0);
    $importResult.html('');
  };

  /**
   * Fired when the dialog's close/cancel button is clicked.
   */
  var close = function() {
    $importer.dialog('close');
  };

  /**
   * Callback fired periodically when the file is being uploaded, increase
   * the progress bar to represent this progress.
   *
   * @param {object} e Upload progress event
   */
  var progress = function(e) {
    if (e.lengthComputable) {
      $progressBar.val(Math.ceil((e.loaded / e.total) * 100));
    }
  };

  /**
   * If supported, add the event that will make the progress bar work.
   */
  var xhr = $.ajaxSettings.xhr();
  if (xhr.upload) {
    xhr.upload.addEventListener('progress', progress, false);
  }

  /**
   * Closure for returning XHR
   */
  var provider = function() {
    return xhr;
  };

  /**
   * Fired when an import is complete and the entire page needs to be reloaded
   * so that the newly imported Questions will be rendered.
   */
  var reload = function() {
    window.location.reload();
  };

  /**
   * - Callback fired when the import button is clicked
   * - Grab the file to be uploaded
   * - Create a new FormData element and attach the file to be uploaded,
   *   this is required for the progress bar to work.
   * - Send the file to the API for processing.
   */
  var start = function() {
    var file = $importFile.get(0).files[0];
    var data = new FormData();

    if (!file) {
      alert(TU_IMPORTER._noFileSelected);
      return false;
    }

    $importer.dialog('option', { buttons: {} });

    data.append('tu_func', 'importer:import');
    data.append('tu_args[]', $testID.val());
    data.append('tu_args[]', file);

    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      dataType: 'json',
      cache: false,
      contentType: false,
      processData: false,
      success: imported,
      xhr: provider
    });
  };

  /**
   * - Callback fired when the importer has finished an attempted import
   * - Render the result (summary) of the import and change the dialog buttons
   *   to be post-import buttons (to prevent re-importing).
   *
   * @param {object} result
   */
  var imported = function(result) {
    $importer.dialog('option', { buttons: postButtons });
    $importFile.prop('disabled', true);
    $importResult.html(result.msg);
  };

  /**
   * Buttons to show before an import is done
   */
  var preButtons = [{
    'class': 'button',
    text: TU_IMPORTER._cancel,
    click: close
  }, {
    'class': 'button button-primary',
    text: TU_IMPORTER._import,
    click: start
  }];

  /**
   * Buttons to show once an import has been completed
   */
  var postButtons = [{
    'class': 'button button-primary',
    text: TU_IMPORTER._done,
    click: reload
  }];

  /**
   * The config for the importer dialog box
   */
  var dialogConf = {
    dialogClass: 'tu-dialog',
    modal: true,
    autoOpen: false,
    closeOnEscape: true,
    width: 420,
    buttons: preButtons
  };

  /**
   * Add the events that make the importer work
   */
  $importer.dialog(dialogConf);
  $importerLinks.on('click.tu', open);

});