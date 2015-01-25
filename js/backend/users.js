/* global jQuery, TU_USERS */

jQuery(function($) {
  'use strict';

  /**
   * The bulk actions dropdown box
   */
  var $bulkActions = $('select[name=action]');

  /**
   * The form the bulk action dropdown box lives in
   */
  var $bulkActionForm = $bulkActions.parents('form');

  /**
   * If the current page is the Trainee user-admin page then the administrator
   * can use the bulk action to add trainees to a group.
   *
   * @return {boolean}
   */
  var canBulkGroup = function() {
    return (/on-tu_(trainee|group_manager)/).test( $('body').attr('class') );
  };

  /**
   * - Fired if the current page is allowed to have the option to bulk add
   *   Trainees to a Group
   * - Append an option to the bulk action dropdown box.
   */
  var addBulkGroupActions = function() {
    $bulkActions
      .append( $('<option/>', {
        value: 'tu_add_to_group',
        text:  TU_USERS._addToGroup
      }) )
      .append( $('<option/>', {
        value: 'tu_remove_from_group',
        text:  TU_USERS._removeFromGroup
      }) );
  };

  /**
   * - Fired when the page needs to handle bulk add to group functionality.
   * - Add the add/remove group options
   * - Override the default form event to collect a Group
   */
  var setUpBulkGroup = function() {
    addBulkGroupActions();
    addBulkGroupEvents();
  };

  /**
   * - Listen to when the bulk action form is submitted
   * - Intervene and insert a group ID when appropriate
   */
  var addBulkGroupEvents = function() {
    $bulkActionForm.on('submit.tu', collectGroup);
  };

  /**
   * - Fired when the bulk action form is being submitted
   * - Throw up a prompt box asking for a Group ID
   * - Insert the collected group ID into the form so it is submitted
   *   (first removing any possible existing group ID)
   */
  var collectGroup = function(e) {
    var collecting = (/tu_(add_to|remove_from)_group/).test($bulkActions.val());

    if (!collecting) return;

    var groupId = prompt(TU_USERS._enterGroupIdOrTitle);

    $bulkActionForm
      .find(':input[name="tu_group"]')
      .remove();

    $bulkActionForm
      .append( $('<input/>', {
        name: 'tu_group',
        value: groupId
      }) );
  };

  /**
   * If we are currently on a Group Manager or Trainee user-admin page, set up
   * the bulk action to add users to groups.
   */
  if (canBulkGroup()) {
    setUpBulkGroup();
  }

});