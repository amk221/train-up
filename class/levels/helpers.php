<?php

/**
 * General helper functions for working with Levels
 *
 * @package Train-Up!
 * @subpackage Levels
 */

namespace TU;

class Levels {

  /**
   * factory
   *
   * @param array|object $level
   *
   * @access public
   * @static
   *
   * @return object A Level instance
   */
  public static function factory($level = null) {
    return new Level($level);
  }

  /**
   * find_all
   *
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array Levels that match the args
   */
  public static function find_all($args = array()) {
    $args = array_merge(array(
      'numberposts' => -1,
      'post_type'   => 'tu_level',
      'orderby'     => 'ID menu_order post_title',
      'order'       => 'ASC'
    ), $args);

    return get_posts_as('Levels', $args);
  }

  /**
   * _filter
   *
   * - Fired on `pre_get_posts` (for levels in admin), and `tu_pre_get_levels`.
   * - Limit the levels to ones which are assigned to a Group which the current
   *   group manager is also assigned to.
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
      $ids = filter_ids(tu()->group_manager->access_level_ids, $query->query_vars['post__in']);
      $query->set('post__in', $ids);
    }
    return $query;
  }

  /**
   * get_derestricted_ids
   *
   * - Returns the IDs of Levels that are not assigned to a group.
   * - And therefore, whose Resources and Test are not limited to specific
   *   groups of Trainees.
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function get_derestricted_ids() {
    global $wpdb;

    $cache_id  = 'tu_derestricted_level_ids';
    $level_ids = wp_cache_get($cache_id, '', false, $found) ?: array();

    if ($found) return $level_ids;

    $sql = "
      SELECT     ID, COUNT(m.meta_value) AS groups
      FROM       {$wpdb->posts} p
      LEFT JOIN  {$wpdb->postmeta} m
      ON         m.post_id   = p.ID
      AND        m.meta_key  = 'tu_group'
      WHERE      p.post_type = 'tu_level'
      AND        p.post_status = 'publish'
      GROUP BY   ID
      HAVING     groups < 1
    ";

    foreach ($wpdb->get_results($sql) as $row) {
      $level_ids[] =  $row->ID;
    }

    wp_cache_set($cache_id, $level_ids);

    return $level_ids;
  }

  /**
   * localised_js
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_js() {
    return array(
      '_confirmDelete' => self::confirm_delete_msg()
    );
  }

  /**
   * confirm_delete_msg
   *
   * @access private
   * @static
   *
   * @return string
   */
  private static function confirm_delete_msg() {
    $msg = sprintf(
      __('Are you sure you want to delete this %1$s?', 'trainup'),
      strtolower(tu()->config['levels']['single'])
    );
    $msg .= "\n\n";
    $msg .= sprintf(
      __('Doing so will also delete the related %1$s, %2$s, results & files.', 'trainup'),
      strtolower(tu()->config['resources']['plural']),
      strtolower(tu()->config['tests']['single'])
    );
    return $msg;
  }

  /**
   * get_pass_percentage
   *
   * Accepts a user, and determines a percentage value of how many levels the
   * user has completed (passed) the tests for.
   *
   * This will not be suitable for all installations of Train-Up! due to the
   * different requirements that sites have, and also because it is not
   * recursive. (i.e. It does not check nested level completion). However it
   * is still useful for some occasions.
   *
   * Also consider the fact that not all levels may have a test.
   *
   * @param object $user The user to get pass percentage for
   * @param object $level Optional level to get progress of
   *
   * @access public
   * @static
   *
   * @return
   */
  public static function get_pass_percentage($user, $level = null) {
    if ($level && $level->loaded()) {
      $levels = $level->children;
    } else {
      $levels = Levels::find_all(array(
        'post_parent' => 0
      ));
    }

    $total  = count($levels);
    $passed = 0;

    foreach ($levels as $level) {
      if (!$level->test) {
        continue;
      }

      $archive = $user->get_archive($level->test->ID);
      $passed += (int)$archive['passed'];
    }

    $percent = $total > 0 ? round($passed / $total * 100) : 0;

    return $percent;
  }

}


