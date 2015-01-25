<?php

/**
 * Helper class for working with comments
 *
 * @package Train-Up!
 */

namespace TU;

class Comments_helper {

  /**
   * __construct
   *
   * - Instantiated on plugin start.
   * - Filter all comments appropriately by default.
   * - Prevent Group Managers from editing comments (because they'd be
   *   able to edit comments by other Group Managers / Adminsitrators)
   * 
   * @access public
   */
  public function __construct() {
    add_filter('the_comments', array($this, '_filter'));
    add_filter('map_meta_cap', array($this, '_prevent_editing'), 10, 4);
  }

  /**
   * _filter
   *
   * - Fired before a bunch of Comments are retrieved.
   * - If the current user is a Group manager, filter out comments made by
   *   Trainees who are not in a Group that the Group manager manages.
   * - Also filter out comments made by Group Managers who are managers of
   *   a groups that ths current group manager doesn't have access to.
   * - Return array values, to re-index the array.
   * 
   * @param object $comments 
   *
   * @access public
   * @static
   *
   * @return object The altered comments
   */
  public static function _filter($comments) {
    if (isset(tu()->group_manager)) {
      foreach ($comments as $i => $comment) {
        $user = Users::factory($comment->user_id);
        if ($user->is_trainee()) {
          list($allowed) = tu()->group_manager->can_access_trainee($user);
          if (!$allowed) {
            unset($comments[$i]);
          }
        } else if ($user->is_group_manager()) {
          list($allowed) = tu()->group_manager->can_access_group_manager($user);
          if (!$allowed) {
            unset($comments[$i]);
          }
        }
      }
    }

    return array_values($comments);
  }

  /**
   * _prevent_editing
   *
   * - Fired on `map_meta_cap`
   * - If the currently logged in user is a Group Manager, don't let them
   *   edit comments.
   * 
   * @param mixed $caps
   * @param mixed $cap
   * @param mixed $user_id
   * @param mixed $args
   *
   * @access public
   *
   * @return mixed Value.
   */
  function _prevent_editing($caps, $cap, $user_id, $args) {
    if ($cap === 'edit_comment' && tu()->user->is_group_manager()) {
      $caps[] = 'moderate_comments';
    }
    
    return $caps;
  }

}


 
