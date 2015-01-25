<?php

/**
 * General helper functions for working with Users
 *
 * @package Train-Up!
 * @subpackage Users
 */

namespace TU;

class Users {

  /**
   * factory
   * 
   * @param array|object $user
   *
   * @access public
   * @static
   *
   * @return object
   */
  public static function factory($user = null) {
    return new User($user);
  }

  /**
   * find_all
   * 
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array Users that match the args
   */
  public static function find_all($args = array()) {
    return get_users_as('Users', $args);
  }

  /**
   * localised_js
   *
   * Returns the hash of localised vars to be used with the user administration
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_js() {
    $_group = strtolower(tu()->config['groups']['single']);

    return array(
      '_addToGroup'          => sprintf(__('Add to %1$s', 'trainup'), $_group),
      '_removeFromGroup'     => sprintf(__('Remove from %1$s', 'trainup'), $_group),
      '_enterGroupIdOrTitle' => sprintf(__('Enter %1$s ID or title', 'trainup'), $_group)
    );
  }

  /**
   * generate_username
   *
   * Given a first and last name, generate a user name making sure it is unique.
   * Use the format jbloggs[n] for Joe Bloggs
   * 
   * @param string $first_name
   * @param string $last_name
   *
   * @access public
   * @static
   *
   * @return string
   */
  public static function generate_username($first_name, $last_name) {
    $name     = $first_name{0} . $last_name;
    $username = $name;
    $i        = 1;

    while (true) {
      $username = $i === 1 ? $name : $name.$i;

      if (get_user_by('login', $username)) {
        $i++;
      } else {
        return strtolower($username);
      }
    }
  }

  /**
   * find_by_activation_key
   *
   * Find a user by the key. If one is found, then only they must have known
   * about the key, so we can assume they are legit.
   * 
   * @param string $key
   *
   * @access public
   * @static
   *
   * @return object|null
   */
  public static function find_by_activation_key($key) {
    global $wpdb;
    
    $sql = "
      SELECT *
      FROM   {$wpdb->users}
      WHERE  user_activation_key = %s
      AND    user_activation_key IS NOT NULL
      AND    user_activation_key != ''
    ";

    $row  = $wpdb->get_row($wpdb->prepare($sql, $key));
    $user = count($row) ? Users::factory($row) : null;

    return $user;
  }

  /**
   * do_swaps
   *
   * - Swap simple 'variables' in the string for the user properties, basically
   *   mimicing shortcodes.
   * - Also add an actual shortcode temporarily to allow the administrator
   *   to determine whether or not the user exists.
   * 
   * @param object $user
   * @param string $string
   *
   * @access public
   * @static
   *
   * @return string
   */
  public static function do_swaps($user, $string = '') {
    $exists       = $user->loaded();
    $known_user   = function($a, $c) use ($exists) { if ($exists)  return $c; };
    $unknown_user = function($a, $c) use ($exists) { if (!$exists) return $c; };

    add_shortcode('known_user', $known_user);
    add_shortcode('!known_user', $unknown_user);

    if ($exists) {
      foreach ($user->get_all_data() as $key => $value) {
        $string = str_replace("[$key]", $value ?: '', $string);
      }
    }

    $string = do_shortcode($string);

    remove_shortcode('known_user', $known_user);
    remove_shortcode('!known_user', $unknown_user);

    return $string;
  }

  /**
   * autocomplete
   * 
   * @param string $search_str
   * @param string $role Optional role to further restrict the autocompletion.
   * @param string $value The autocompletion value to return for each result
   *
   * @access public
   * @static
   *
   * @return array Suitable JSON for use with jQuery UI's autocompleter
   */
  public static function ajax_autocomplete($search_str, $role = null,  $value = 'display_name') {
    global $wpdb;

    $role          = $wpdb->esc_like($role);
    $search        = $wpdb->esc_like($search_str);
    $autocompleted = array();
    $role_filter   = $role ? "AND m.meta_value LIKE '%{$role}%'" : '';

    $sql = "
      SELECT   ID, CONCAT('', {$value}) as user_value
      FROM     {$wpdb->users} u
      JOIN     {$wpdb->usermeta} m
      ON       m.user_id = u.ID
      WHERE    m.meta_key = '{$wpdb->prefix}capabilities'
      {$role_filter}
      AND      CONCAT(user_nicename, ' ', user_email, ' ', display_name)
      LIKE     '%{$search}%'
      ORDER BY u.display_name ASC
      LIMIT    5
    ";

    foreach ($wpdb->get_results($sql) as $user) {
      $autocompleted[] = array(
        'label' => $user->user_value,
        'value' => $user->ID
      );
    }

    return $autocompleted;
  }

}


 
