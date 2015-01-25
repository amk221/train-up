<?php

/**
 * The admin screen for working with Pages
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Page_admin extends Post_admin {

  /**
   * __construct
   * 
   * - Load the Page post type, and refresh it.
   * - Construct the admin page as normal
   * - If this admin page is active, add a filter the the posts to set the
   *   order to always be ASC title order.
   *
   * @access public
   */
  public function __construct() {
    $post_type = new Page_post_type;
    $post_type->refresh();

    parent::__construct($post_type);

    if ($this->is_active()) {
      add_filter('map_meta_cap', array($this, '_prevent_deletion'), 10, 4);
    }
  }

  /**
   * get_columns
   *
   * - Returns the extra columns to show on this page.
   * - Return nothing, because we actually want to turn off the extra menu
   *   order column on the Pages admin screens.
   * 
   * @access protected
   *
   * @return array|null
   */
  protected function get_columns() {

  }

  /**
   * get_meta_boxes
   * 
   * @access protected
   *
   * @return array Hash of meta boxes to display on the Pages admin
   */
  protected function get_meta_boxes() {
    return array(
      'shortcodes' => array(
        'title'    => __('Shortcodes', 'trainup'),
        'context'  => 'side',
        'priority' => 'high',
        'closed'   => true
      )
    );
  }

  /**
   * _set_order
   *
   * - Fired on `pre_get_posts` when Pages are being viewed
   * - Override the default _set_order function (by menu_order) and
   *   set it to go off the title instead, unless otherwise specified.
   *
   * @param object $query
   * 
   * @access private
   *
   * @return object The altered query
   */
  public function _set_order($query) {
    if (!isset($_REQUEST['orderby'])) {
      $query->set('orderby', 'title');
      $query->set('order', 'ASC');
    }

    return $query;
  }

  /**
   * _prevent_deletion
   *
   * - Fired on `map_meta_cap`
   * - If the post in question has a tu_class post meta, then it is a Train-Up!
   *   post with a specific PHP class to handle it, and therefore users should
   *   not be able to delete it.
   * 
   * @param mixed $caps
   * @param mixed $cap
   * @param mixed $user_id
   * @param mixed $args
   *
   * @access private
   *
   * @return array
   */
  public function _prevent_deletion($caps, $cap, $user_id, $args) {
    if (
      $cap === 'delete_post' &&
      get_post_meta($args[0], 'tu_class', true)
    )

    $caps[] = 'do_not_allow';

    return $caps;
  }
}


 
