/* global jQuery, TU, ajaxurl */

jQuery(function($) {
  'use strict';

  /**
   * All autocompleter text field elements
   */
  var $autocompleters = $('.tu-autocompleter');

  /**
   * - Callback in the context of a text field
   * - Convert this text field into an autocompleter, each one's config
   *   is taken from their data attribtues.
   */
  var autocompleterify = function() {

    /**
     * jQueryify this text field
     */
    var $autocompleter = $(this);

    /**
     * Try to load this text field's associated hidden input
     */
    var $input = $autocompleter.prev('input');

    /**
     * Load the name of this autocompleter, this is the name of the form input
     * that will be submitted when something is autocompleted.
     */
    var name = $autocompleter.data('name');

    /**
     * Read-in the string that relates to a back-end function. This relates to 
     * a namespace of something that is to be autocompleted.
     */
    var autocomplete = $autocompleter.data('autocomplete');

    /**
     * Generate the name of the function that is to be called via the API
     * and will return the autocompleter suggestions.
     */
    var func = autocomplete.toLowerCase() + ':autocomplete';

    /**
     * Object containing information about the last selected autocompleter
     * suggestion.
     */
    var selected = {};

    /**
     * If no associated hidden form input is provided, then make one
     */
    if (!$input.length) {
      $input = $('<input/>', { type: 'hidden', name: name });
      $autocompleter.before($input);
    }

    /**
     * - Callback fired on keyup on the text field that does the autocompleting
     * - If the value hasn't changed since the last suggestion, just set the
     *   value in the hidden form input. Otherwise, this must be a new
     *   suggestion so clear the form input.
     *
     * @param {object} e Key up event
     */
    var reset = function(e) {
      if (selected.label === $autocompleter.val()) {
        $input.val(selected.value);
      } else {
        $input.val('');
      }
    };

    /**
     * - Callback fired when an autocompleter suggestion is selected
     * - Cache the selected item internally
     * - Update the value of the autocompleter text field to be the label
     *   of the suggested item.
     * - Update the value of the associated hidden form input with the value
     *   of the suggested item.
     * - This is to keep what is visually autocompleted separate from the 
     *   actual value that is submitted.
     *
     * @param {object} e
     * @param {object} ui
     */
    var select = function(e, ui) {
      selected = ui.item;
      e.preventDefault();
      $autocompleter.val(ui.item.label);
      $input.val(ui.item.value);
    };

    /**
     * - Callback fired when the user uses the arrow keys to navigate auto-
     *   completer suggestions.
     * - Set the text field's value to be the label of the suggestion
     */
    var preview = function(e, ui) {
      e.preventDefault();
      $autocompleter.val(ui.item.label);
    };

    /**
     * - Callback fired when the autocompleter requests data
     * - This function specifies the source of data for *this* autocompleter
     *   text field.
     */
    var source = function(req, response) {
      return $.ajax({
        type: 'POST',
        url: TU.ajaxUrl,
        success: response,
        dataType: 'json',
        data: {
          tu_func: func,
          tu_args: [req.term]
        }
      });
    };

    /**
     * Initialise this autocompleter
     */
    $autocompleter.autocomplete({
      select: select,
      focus: preview,
      source: source
    });

    /**
     * When the autocompleter is interacted with, reset its associated
     * hidden form input value.
     */
    $autocompleter.keyup(reset);
  };

  /**
   * Initialise all the autocompleters automatically
   */
  $autocompleters.each(autocompleterify);

});