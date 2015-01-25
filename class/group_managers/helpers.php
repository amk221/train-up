<?php

/**
 * General helper functions for working with Group managers
 *
 * @package Train-Up!
 * @subpackage Group managers
 */

namespace TU;

class Group_managers {

  /**
   * factory
   *
   * @param array|object $group_manager 
   *
   * @access public
   * @static
   *
   * @return object A Group manager instance
   */
  public static function factory($group_manager = null) {
    return new Group_manager($group_manager);
  }

  /**
   * find_all
   * 
   * @param array $args 
   *
   * @access public
   * @static
   *
   * @return array Group managers that match the args 
   */
  public static function find_all($args = array()) {
    return get_users_as('Group_managers', array_merge($args, array(
      'role' => 'tu_group_manager'
    )));
  }
  
}
 
