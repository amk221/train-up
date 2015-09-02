<?php

/**
 * General helper functions for working with Pages
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Pages {

  /**
   * factory
   *
   * - Returns a new page object.
   * - If the paramater passed is a string, it is assumed to be the class name
   *   of the Class that the page is associated with, and so in that case we
   *   return an instance of *that* type, rather than the generic page class.
   * - e.g. Pages::factory('Login') returns a new Login_page instance
   *
   * @param array|object $page
   * @param boolean $active Spoof whether or not the page is active
   *
   * @access public
   * @static
   *
   * @return object A Page instance
   */
  public static function factory($page = null, $active = false) {
    $class_name = __NAMESPACE__.'\\'.(
      is_string($page) ? "{$page}_page" : 'Page'
    );

    return new $class_name($page, $active);
  }

  /**
   * find_all
   *
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array Pages that match the args
   */
  public static function find_all($args = array()) {
    $args = array_merge(array(
      'numberposts' => -1,
      'post_type'   => 'tu_page',
      'orderby'     => 'ID menu_order post_title',
      'order'       => 'ASC'
    ), $args);

    return get_posts_as('Pages', $args);
  }

}


