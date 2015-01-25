<?php

/**
 * General helper functions for working with Trainees
 *
 * @package Train-Up!
 * @subpackage Trainees
 */

namespace TU;

class Trainees {

  /**
   * factory
   * 
   * @param array|object $trainee
   *
   * @access public
   * @static
   *
   * @return object A Trainee instance
   */
  public static function factory($trainee = null) {
    return new Trainee($trainee);
  }

  /**
   * find_all
   * 
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array Trainees that match the args
   */
  public static function find_all($args = array()) {
    return get_users_as('Trainees', array_merge($args, array(
      'role' => 'tu_trainee'
    )));
  }

  /**
   * _filter
   *
   * - Fired on `pre_get_posts` when the trainee list view is active
   * - Limit the trainees to ones which are assigned to a Group which the
   *   current group manager is also assigned to, i.e. Group managers should not
   *   be allowed to see or edit administrators/other user types.
   * 
   * @param object $query
   *
   * @access public
   * @static
   *
   * @return object The altered query
   */
  public static function _filter($query) {
    if (tu()->user->is_group_manager()) {
      $trainee_ids = filter_ids(tu()->group_manager->access_trainee_ids);
      $query->query_where .= " AND ID IN (".join(',', $trainee_ids).")";
    }
    return $query;  
  }

  /**
   * ajax_autocomplete
   *
   * - Accept a search term to find a Trainee, return the ID and value (name)
   *   of that Trainee in an array of hashes. Suitable for autocompletion.
   * - This function just utilised the Users autocompletion function, but
   *   limits it to Trainees.
   * 
   * @param string $search_str
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function ajax_autocomplete($search_str) {
    return Users::ajax_autocomplete($search_str, 'tu_trainee');
  }

  /**
   * taking_test_regex
   *
   * Returns a regular expression that matches something like:
   * - "taking_test: 1"
   * - "taking_exam: 2"
   * This is for letting administrators search for trainees who are currently
   * taking a test, i.e. they've started it but not finished it.
   * 
   * @access public
   * @static
   *
   * @return string
   */
  public static function taking_test_regex() {
    $_test = simplify(tu()->config['tests']['single']);

    return "/taking_(test|{$_test}):\s?([\d]+)/i";
  }

  /**
   * get_ungrouped_ids
   * 
   * @access public
   * @static
   *
   * @return array The IDs of Trainees who are not in a Group, and therefore can
   * be accessed by any Group manager.
   */
  public static function get_ungrouped_ids() {
    global $wpdb;

    $cache_id    = 'tu_ungrouped_trainee_ids';
    $trainee_ids = wp_cache_get($cache_id, '', false, $found) ?: array();

    if ($found) return $trainee_ids;

    $sql = "
      SELECT    *, ID, COUNT(m3.meta_value) AS groups
      FROM      {$wpdb->users} u
      JOIN      {$wpdb->usermeta} m1
      ON        u.ID = m1.user_id
      AND       m1.meta_key = '{$wpdb->prefix}capabilities'
      JOIN      {$wpdb->usermeta} m2
      ON        u.ID = m2.user_id
      AND       m2.meta_value LIKE '%tu_trainee%'
      LEFT JOIN {$wpdb->usermeta} m3 ON u.ID = m3.user_id
      AND       m3.meta_key = 'tu_group'
      GROUP BY  ID
      HAVING    groups < 1
    ";

    foreach ($wpdb->get_results($sql) as $row) {
      $trainee_ids[] = $row->ID;
    }

    wp_cache_set($cache_id, $trainee_ids);

    return $trainee_ids;
  }

}


 
