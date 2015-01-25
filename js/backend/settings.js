/* global jQuery, TU */

jQuery(function($) {
  'use strict';

  /**
   * Elements that wrap WYSIWYG elements
   */
  var $editorsContainers = $('.tu-template-editor-container');

  /**
   * Buttons that toggle visibility of their associated WYSIWYG editor
   */
  var $editorButtons = $('.tu-template-editor-button');

  /**
   * The editors themselves
   */
  var $editors = $editorsContainers.find('.wp-editor-wrap');

  /**
   * - Callback fired when a button is clicked that is to show its associated
   *   WYSIWYG editor.
   * - Hide all other editors and show this one.
   *
   * @param {object} e Click event
   */
  var showTemplateEditor = function(e) {
    e.preventDefault();

    var $button = $(this);
    var $editor = $button.parent().find('.wp-editor-wrap');
    var $iframe = $editor.find('iframe');

    $editors.hide();
    $editorButtons.show();
    $button.hide();
    $editor.show();
    $iframe.height('300px');
  };

  /**
   * When editor buttons are clicked, show their related editor
   */
  $editorButtons.on('click.tu', showTemplateEditor);

});
