/* global jQuery, TU, ajaxurl, alert, confirm, window */

jQuery(function($) {
  'use strict';

  /**
   * The body element, used for attaching useful classnames to aid styling
   */
  var $body = $('body');

  /**
   * Loading namespace.
   * Some useful functions to start and stop loading (in combination with CSS).
   * This is for the backend, however there is an equivalent function
   * on the front end JavaScripts too.
   */
  TU.loading = {
    start: function() {
      $body.addClass('tu-loading');
    },
    stop: function() {
      $body.removeClass('tu-loading');
    }
  };

});