/* global jQuery, TU_LEVELS, confirm */

jQuery(function($) {
  'use strict';

  /**
   * Link elements that delete a Level 
   */
  var $deleteLinks = $('.type-tu_level .delete .submitdelete');

  /**
   * When a Level is attempted to be deleted, confirm with the user first
   * because potentially a lot of information will be lost (all the relations).
   */
  var confirmDelete = function() {
    return confirm(TU_LEVELS._confirmDelete);
  };

  /**
   * Check with the user before deleting a Level
   */
  $deleteLinks.on('click.tu', confirmDelete);

});