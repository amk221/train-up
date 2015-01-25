/* global jQuery, TU_EMAILER, tinymce, ajaxurl */

jQuery(function($) {
  'use strict';

  /**
   * The bulk actions dropdown box
   */
  var $bulkActions = $('select[name=action]');

  /**
   * The emailer container element
   */
  var $emailer = $('.tu-emailer');

  /**
   * The add recipient button
   */
  var $addRecipient = $emailer.find('.tu-add-recipient');

  /**
   * The remove recipient button
   */
  var $removeRecipient = $emailer.find('.tu-remove-recipient');

  /**
   * The autocompleter text field that allows searching for a user.
   */
  var $userSearch = $emailer.find('.tu-autocompleter');

  /**
   * The recipients multiple select box
   */
  var $recipients = $emailer.find('.tu-recipients');

  /**
   * The select box that lets you choose who sent the email
   */
  var $from = $emailer.find('select[name=from]');

  /**
   * The subject line text box
   */
  var $subject = $emailer.find('input[name=subject]');

  /**
   * The email body textarea
   */
  var $body = $emailer.find('textarea[name=body]');

  /**
   * The button to send the email.
   */
  var $send = $emailer.find('.button-primary');

  /**
   * An element to house the any response messages from the emailer
   */
  var $response = $emailer.find('.tu-emailer-response');

  /**
   * The latest response from the emailer
   */
  var response = '';

  /**
   * An array of classnames (applied to the body), which imply that the page
   * currently on is bulk-emailable or not.
   */
  var bulkEmailable = [
    'on-tu_trainee',
    'on-tu_group_manager',
    'on-tu_group',
    'on-tu_result'
  ];

  /**
   * @return {boolean} Whether or not the current page is the emailer
   */
  var onEmailer = function() {
    return (/page_tu_emailer/).test($('body').attr("class"));
  };

  /**
   * If the current page has one of the supported classnames on its body element
   * then bulk emailing is allowed. This is a bodge because WordPress does not
   * have a very good serverside API for manipulating bulk actions.
   *
   * @return {boolean}
   */
  var isBulkEmailable = function() {
    var emailable = false;

    for (var i in bulkEmailable) {
      if ($('body').hasClass(bulkEmailable[i])) {
        emailable = true;
        break;
      }
    }

    return emailable;
  };

  /**
   * - Fired if the current page is allowed to have the option to bulk email.
   * - Append an option to the bulk action dropdown box.
   */
  var addBulkActions = function() {
    $bulkActions.append( $('<option/>', {
      value: 'tu_send_email',
      text:  TU_EMAILER._sendEmail
    }) );
  };

  /**
   * @return {boolean} Whether or not the given address is already contained
   * in the recipients select box.
   */
  var addressInRecipients = function(address) {
    return !!$recipients.find('option:contains('+ address +')').length;
  };

  /**
   * - Callback fired when the add recipient button is clicked
   * - If they've not already been added to the recipient list, then add them
   *   and clear the autocompleter text field.
   */
  var addRecipient = function(e) {
    e.preventDefault();

    var address = $userSearch.val();

    if (address && !addressInRecipients(address)) {
      insertRecipient();
      $userSearch.val('');
    }
  };

  /**
   * - Callback fired when the remove recipient button is clicked
   * - Remove the selected recipients from the dropdown box.
   */
  var removeRecipient = function(e) {
    e.preventDefault();

    $recipients.find(':selected').remove();
  };

  /**
   * Move the autocompleted user's email address into the recipients list.
   */
  var insertRecipient = function() {
    var $option = $('<option/>', {
      text: $userSearch.val()
    });

    $recipients.append($option);
  };

  /**
   * Disable the emailer by preventing any buttons from being clicked.
   */
  var disable = function() {
    $emailer.find(':input, .button').attr('disabled', 'disabled');
  };

  /**
   * Re-enable the emailer by allowing the buttons to be clicked.
   */
  var enable = function() {
    $emailer.find(':input, .button').removeAttr('disabled');
  };

  /**
   * - Empty the recipients list
   * - Empty the subject line
   * - Empty the body
   */
  var reset = function() {
    $recipients.empty();
    $subject.val('');
    setContent('');
  };

  /**
   * Hide the DOM element that houses the textual response from the emailer.
   */
  var hideResponse = function() {
    $response.empty().hide();
  };

  /**
   * Insert the latest response into the response element, then fade it in.
   */
  var showResponse = function() {
    $response.html(response.msg).delay(300).fadeIn('fast');
  };

  /**
   * - Callback fired when the emailer API comes back with its response
   * - Re-set the send button's text to 'Send' (it was 'Sending...')
   * - Store the response
   * - Re-enable the emailer now that it has finished emailing
   * - If emails were sent, it was successful so reset the form.
   * - Scroll to the response.
   *
   * @param {object} data from the API
   */
  var sent = function(data) {
    $send.text(TU_EMAILER._send);

    response = data;
    enable();

    if (response.sent > 0) {
      reset();
    }

    $('html, body').animate({ scrollTop: 0 }, 200, showResponse);
  };

  /**
   * Get the body content for the email.
   * Note: The element is a WYSIWYG editor, and so there are two ways to 
   * retreive its content depending on whether or not it is in HTML mode
   * or preview mode.
   *
   * @return {string} Body text/markup
   */
  var getContent = function() {
    var editor = tinymce.get('tuemailer');
    return $.type(editor) === 'object' ? editor.getContent() : $body.val();
  };

  /**
   * Update the WYSIWYG editor with the content provided, baring in mind that
   * the element may be in HTML mode or preview mode.
   *
   * @param {string} content
   */
  var setContent = function(content) {
    var editor = tinymce.get('tuemailer');
    if ($.type(editor) === 'object') {
      editor.setContent(content);
    } else {
      $body.val(content);
    }
  };

  /**
   * - Fired when the emailer form is submitted
   * - Temporarily set the button text to 'Sending...'
   * - Clone the recipients list and select all the recipients (so as to not
   *   visually confuse the user by selecting them all)
   * - Hide any previous response from the emailer
   * - Disable the emailer form whilst emailing is in progress.
   *
   * @param {object} e Submit event
   */
  var send = function(e) {
    e.preventDefault();

    $send.text(TU_EMAILER._sending);

    var $to = $recipients.clone();
    $to.find('option').prop('selected', true);

    hideResponse();
    disable();

    $.ajax({
      type: 'POST',
      url: ajaxurl,
      success: sent,
      dataType: 'json',
      data: $.param({
        tu_func: 'emailer:send',
        tu_args: [
          $to.val(),
          $from.val(),
          $subject.val(),
          getContent()
        ]
      })
    });
  };

  /**
   * Add events that make the emailer work.
   */
  var addEvents = function() {
    $emailer.on('submit.tu', send);
    $addRecipient.on('click.tu', addRecipient);
    $removeRecipient.on('click.tu', removeRecipient);
  };

  /**
   * If we're on a page that is allowed to have the bulk-email option, then
   * add it to the dropdown box.
   */
  if (isBulkEmailable()) {
    addBulkActions();
  }

  /**
   * If we are currently on the emailer page, then initialise it by adding
   * the DOM events that make it work.
   */
  if (onEmailer()) {
    addEvents();
  }

});