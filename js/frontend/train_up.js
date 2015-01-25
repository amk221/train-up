/* global jQuery, TU */

jQuery(function($) {
  'use strict';

  /**
   * The HTML element, used for adding useful classnames to aid styling
   */
  var $html = $('html');

  /**
   * Load the common flash messages element
   */
  TU.$flash = $('.tu-flash');

  /**
   * Loading namespace.
   * Useful functions for controlling state.
   */
  TU.loading = {
    start: function() {
      $html.addClass('tu-loading');
    },
    stop: function() {
      $html.removeClass('tu-loading');
    }
  };

});