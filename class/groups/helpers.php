<?php

/**
 * General helper functions for working with Groups
 *
 * @package Train-Up!
 * @subpackage Groups
 */

namespace TU;

class Groups {

  /**
   * factory
   * 
   * @param array|object $group 
   *
   * @access public
   * @static
   *
   * @return object A Group instance
   */
  public static function factory($group = null) {
    return new Group($group);
  }

  /**
   * find_all
   * 
   * @param array $args 
   *
   * @access public
   * @static
   *
   * @return array Groups that match the args
   */
  public static function find_all($args = array()) {
    $args = array_merge(array(
      'numberposts' => -1,
      'post_type'   => 'tu_group',
      'orderby'     => 'post_title',
      'order'       => 'ASC'
    ), $args);
    
    return get_posts_as('Groups', $args);
  }

  /**
   * _filter
   *
   * - Fired when Groups are being loaded
   * - If the current user is a Group manager, filter the Groups that are being
   *   loaded to only show those which the Group manage actually manages.
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
      $ids = filter_ids(tu()->group_manager->group_ids, $query->query_vars['post__in']);
      $query->set('post__in', $ids);
    }
    return $query;
  }

  /**
   * get_trainee_ids
   * 
   * @param array $group_ids
   *
   * @access public
   * @static
   *
   * @return array The IDs of Trainees who are in a bunch of groups.
   */
  public static function get_trainee_ids($group_ids) {
    global $wpdb;

    if (count($group_ids) < 1) return array();

    $cache_grp   = 'tu_trainee_ids_for_groups';
    $cache_id    = md5(serialize(func_get_args()));
    $trainee_ids = wp_cache_get($cache_id, $cache_grp, false, $found) ?: array();

    if ($found) return $trainee_ids;

    $sql = "
      SELECT DISTINCT ID FROM {$wpdb->users} u
      JOIN   {$wpdb->usermeta} m1
      ON     u.ID = m1.user_id
      JOIN   {$wpdb->usermeta} m2
      ON     u.ID = m2.user_id
      WHERE  m1.meta_key = 'tu_group'
      AND    m1.meta_value IN(".join(',', $group_ids).")
      AND    m2.meta_key = '{$wpdb->prefix}capabilities'
      AND    m2.meta_value LIKE '%tu_trainee%'
    ";

    foreach ($wpdb->get_results($sql) as $row) {
      $trainee_ids[] = $row->ID;
    }

    wp_cache_set($cache_id, $trainee_ids, $cache_grp);

    return $trainee_ids;
  }

  /**
   * for_regex
   *
   * Returns a regular expression that matches something like:
   * - "trainee: 6"
   * - "group_manager: 7"
   * - "student: 8"
   * - "teacher: 9"
   * This is for letting administrators search for users who have stuff
   * associated with them, for example Groups.
   * 
   * @access public
   * @static
   *
   * @return string
   */
  public static function for_regex() {
    $_trainee       = simplify(tu()->config['trainees']['single']);
    $_group_manager = simplify(tu()->config['group_managers']['single']);

    return "/(trainee|group_manager|{$_trainee}|{$_group_manager}):\s?([\d]+)/i";
  }

  /**
   * in_regex
   *
   * Return a regular expression that matches something like:
   * - "groups: 10"
   * - "group: 11"
   * - "class: 12"
   * - "classes: 12"
   * This is for letting administrators search for users who are in a certain
   * group or groups.
   * 
   * @access public
   * @static
   *
   * @return string
   */
  public static function in_regex() {
    $_group  = simplify(tu()->config['groups']['single']);
    $_groups = simplify(tu()->config['groups']['plural']);

    return "/(groups?|{$_group}|{$_groups}):\s?([,\d]+)?/i";
  }

  /**
   * autocomplete
   *
   * @param string $search_str Search term to find a Group
   *
   * @access public
   * @static
   *
   * @return array containing The ID and value (title) of that Group
   */
  public static function ajax_autocomplete($search_str) {
    $query         = new \WP_Query;
    $autocompleted = array();
    $groups        = $query->query(array(
      's'              => $search_str,
      'post_type'      => 'tu_group',
      'posts_per_page' => 5,
      'orderby'        => 'title',
      'order'          => 'ASC'
    ));

    foreach ($groups as $group) {
      $autocompleted[] = array(
        'label' => $group->post_title,
        'value' => $group->ID
      );
    }

    return $autocompleted;
  }

}


