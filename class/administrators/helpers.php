<?php

/**
 * General helper functions for working with Administrators
 *
 * @package Train-Up!
 * @subpackage Administrators
 */

namespace TU;

class Administrators {

  /**
   * factory
   *
   * @param array|object $administrator 
   *
   * @access public
   * @static
   *
   * @return object an Administrator instance
   */
  public static function factory($administrator = null) {
    return new Administrator($administrator);
  }

  /**
   * find_all
   *
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array Administrators that match args passed in
   */
  public static function find_all($args = array()) {
    return get_users_as('Administrators', array_merge($args, array(
      'role' => 'administrator'
    )));
  }

  /**
   * mailing_list
   *
   * Returns an array of the email addresses of all Administrators in the
   * system. Suitable for being passed straight to the Emailer.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function mailing_list() {
    $emails = array();
    foreach (self::find_all() as $administrator) {
      $emails[] = $administrator->user_email;
    }
    return $emails;
  }

}