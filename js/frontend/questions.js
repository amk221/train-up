/* global jQuery, TU, TU_QUESTIONS, tinyMCE, FormData */

jQuery(function($) {
  'use strict';

  /**
   * The form element that contains the trainee's answers
   */
  var $form = $('.tu-answers');

  /**
   * The page elements (used for scrolling events).
   */
  var $page = $('html, body');

  /**
   * The main button that submits the form.
   */
  var $saveButton = $form.find(".tu-form-button button[type=submit]");

  /**
   * The original save buttons text, so we can restore the label
   */
  var saveButtonText = $saveButton.text();

  /**
   * Files upload fields that exist in the form
   */
  var $files = $form.find(':file[name^=tu_file_attachment]');

  /**
   * Callback fired periodically when files are being uploaded, expose the
   * percentage progress on the TU namespace and update the form button to
   * show progress.
   * 
   * @param {object} e Upload progress event
   */
  var progress = function(e) {
    if (e.lengthComputable && $files.length >= 1) {
      TU_QUESTIONS.uploadProgress = Math.ceil((e.loaded / e.total) * 100);
      $saveButton.text(
        TU_QUESTIONS._uploading + ' ' + TU_QUESTIONS.uploadProgress + '%'
      );
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
   * Build a FormData object containing the 2 main parameters,
   * tu_func (the end point API function that will handle the form submission)
   * tu_args (the data that gets mapped to function arguments on the API)
   */
  var getFormData = function() {
    var data        = new FormData();
    var requestData = $form.serialize() + '&tu_question_id=' + TU.activePostId;

    data.append('tu_func', 'questions:save_answer');
    
    // $_REQUEST

    data.append('tu_args[]', requestData);

    // $_FILES

    $files.each(function() {
      var file = $(this).get(0).files[0];
      if (file) {
        data.append('tu_args[]', file);
      }
    });

    return data;
  };

  /**
   * - Callback fired when the trainee's answers form is submitted
   * - Make sure any WYSIWYG content is transferred to their corresponding textareas
   * - Submit the form, sending the request data, *and* any file data too.
   * 
   * @param {object} e The submit event
   */
  var save = function(e) {
    e.preventDefault();

    if (typeof tinyMCE !== 'undefined') {
      tinyMCE.triggerSave();
    }

    $.ajax({
      type: 'POST',
      url: TU.ajaxUrl,
      success: saved,
      dataType: 'json',
      beforeSend: TU.loading.start,
      complete: TU.loading.stop,
      data: getFormData(),
      cache: false,
      contentType: false,
      processData: false,
      xhr: provider
    });
  };

  /**
   * - Callback fired when a trainee's answers were saved successfully via ajax.
   * - Restore the save button text (if files were uploaded)
   * - The response contains some generated markup, hide this element then
   *   show it by fading it in and scrolling up to it.
   *
   * @param {object} response From the API
   */
  var saved = function(response) {
    $saveButton.text(saveButtonText);

    var $msg = $(response.msg_html);
    $msg.hide();

    var showResponse = function() {
      TU.$flash.html($msg);
      $msg.fadeIn();
    };

    $page.animate({ scrollTop: TU.$flash.offset().top }, 200, showResponse);
  };

  /**
   * When the answers form is submitted, save the answers if Ajax saving is on.
   * Note: XMLHttpRequest Level 2 is required for use of FormData.
   */

  if (TU_QUESTIONS.saveViaAjax && typeof FormData !== 'undefined') {
    $form.on('submit.tu', save);
  }

});