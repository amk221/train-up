/* global jQuery, document, window, TU */

jQuery(function($) {
  'use strict';

  /**
   * The cached document object
   */
  var $d = $(document);

  /**
   * - Fired on keyup
   * - Check if an arrow key has been pressed and if so prevent the page from
   *   loading more to improve perceived responsiveness of the keypress then go
   *   forwards or backwards depending on what was pressed. 
   *
   * @param {object} e The keyup event
   */
  var navigate = function(e) {
    var direction = e.which === 37 ? 'prev' : ( e.which === 39 ? 'next' : '' );
    var url = TU[direction + 'PostUrl'];

    if (direction && url) {
      if ('stop' in window) window.stop();
      window.location.href = url;
    }
  };

  /**
   * - Fired on blur or focus of a form input
   * - Turn the listener on or off that responds to arrow key presses.
   */
  var toggle = function(e) {
    $d[e.data]('keyup.tu', navigate);
  };

  /**
   * Turn off keyboard navigation when form elements are focused,
   * and turn it back on again when they are un-focused.
   * This is because users may want to navigate text fields using the arrow keys
   */
  $d.on('focus.tu', ':input', ['off'], toggle);
  $d.on('blur.tu', ':input', ['on'], toggle);

  /**
   * Turn on arrow key navigation
   */
  toggle.call(null, { data: 'on' });

});
