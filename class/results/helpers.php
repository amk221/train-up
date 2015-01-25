<?php

/**
 * General helper functions for working with Results
 *
 * @package Train-Up!
 * @subpackage Results
 */

namespace TU;

class Results {

  /**
   * factory
   *
   * @param array|object $result 
   *
   * @access public
   * @static
   *
   * @return object A Result instance
   */
  public static function factory($result = null) {
    return new Result($result);
  }

  /**
   * _filter
   *
   * Fired before a bunch of Results are retrieved. If the current user
   * is a Group manager, limit the Results to those whose Trainee is in the
   * Groups that the Group manager manages.
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
      $ids = filter_ids(tu()->group_manager->access_result_ids, $query->query_vars['post__in']);
      $query->set('post__in', $ids);
    }
    return $query;
  }

  /**
   * create
   *
   * Insert a new result for a user's attempt at a given test.
   * 
   * @param object $test The test the user took
   * @param object $user The user who took the test
   * @param array $data Result data to merge with the defaults
   *
   * @access public
   * @static
   *
   * @return object
   */
  public static function create($test, $user, $data = array()) {
    $config = tu()->config['tests'];

    $data = array_merge(array(
      'post_title'   => $user->display_name,
      'post_type'    => "tu_result_{$test->ID}",
      'post_date'    => current_time('mysql'),
      'post_status'  => $test->result_status,
      'post_content' => $config['default_result_content'],
      'ping_status'  => empty($config['result_comments']) ? 'closed' : 'open'
    ), $data);

    $result = self::factory();
    $result->save($data);
    $result->set_user_id($user->ID);
    $result->set_test_id($test->ID);

    return $result;
  } 

  /**
   * ajax_set_manual_percentage
   *
   * - Allow setting of a new percentage for a given test Result via the JS API.
   * - This manipulates the archived test result, but only for the Trainee's
   *   latest attempt at the Test associated with this Result.
   * 
   * @param mixed $result_id
   * @param mixed $percentage
   *
   * @access public
   * @static
   *
   * @return object Re-calculated grade information
   */
  public static function ajax_set_manual_percentage($result_id, $percentage) {
    if (!current_user_can('tu_backend')) return;

    $result = Results::factory($result_id);
    $result->set_manual_percentage($percentage);
    return $result->get_archive();
  }

  /**
   * localised_js
   *
   * JavaScript namespace for Results.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_js() {
    $_test     = strtolower(tu()->config['tests']['single']);
    $_trainees = tu()->config['trainees']['plural'];

    $js = array(
      '_confirmDeleteFiles' => sprintf(__('This will delete old files uploaded to this %1$s. (%2$s latest uploaded files will remain)'), $_test, $_trainees),
      '_filterResults'      => __('Filter results', 'trainup')
    );

    $result = apply_filters('tu_questions_js_namespace', $js);

    return $result;
  }

  /**
   * update_archive
   *
   * - Accept an archive and update its details in the database.
   * - Generally the archive should not be edited, however the ability to
   *   add a response to a Trainee's attempted answers was added at a later
   *   stage and the archive seemed like the best place to store it :/
   *
   * @param object $archive
   *
   * @access public (although, not advised)
   * @static
   *
   */
  public static function update_archive($archive) {
    global $wpdb;

    $new_archive = $archive;
    $table       = "{$wpdb->prefix}tu_archive";
    $data        = $new_archive;
    $where       = $archive;
    unset($where['answers']);

    $wpdb->update($table, $data, $where);
  }

  /**
   * convert_archives_to_csv
   *
   * Accept an array of hashes that is basically just the data straight from the
   * archive database table. Go through and remove some fields that would be of
   * no use in the CSV file and add the localised column headers.
   *
   * Add the rank column if present. It is only relevant when ordered by mark/
   * percentage.
   * 
   * @param mixed $archive
   *
   * @access public
   * @static
   *
   * @return array An array suitable for outputting as a CSV
   */
  public static function convert_archives_to_csv($archives) {
    $rows = array();

    foreach ($archives as &$archive) {
      unset($archive['id']);
      unset($archive['user_id']);
      unset($archive['test_id']);
      unset($archive['answers']);
      unset($archive['latest']);
      $archive['passed'] = $archive['passed'] ? __('Yes', 'trainup') : __('No', 'trainup');
      $rows[] = array_values($archive);
    }

    $headers = array(
      __('Date', 'trainup'),
      __('Duration', 'trainup'),
      __('Name', 'trainup'),
      tu()->config['tests']['single'],
      __('Mark', 'trainup'),
      __('Out of', 'trainup'),
      __('Percentage', 'trainup'),
      __('Grade', 'trainup'),
      __('Passed', 'trainup'),
      __('Resit', 'trainup')
    );

    if (isset($archives[0]) && isset($archives[0]['rank'])) {
      $headers[] = __('Rank', 'trainup');
    }

    $headers_row = array($headers);

    return array_merge($headers_row, $rows);
  }

  /**
   * archives
   *
   * - Similar to user->archives and test->archives
   * - However this sums up each Trainee's total mark for their latest attempt
   *   at *all* tests, rather than a specific test.
   * - Used primarily for the results_table shortcode.
   * - If the user has specified the order to be by percentage, then we can
   *   add an extra column `rank`.
   * 
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function archives($args = array()) {
    global $wpdb;

    $cache_grp = 'tu_archives';
    $cache_id  = md5(serialize(func_get_args()));
    $archives  = wp_cache_get($cache_id, $cache_grp, false, $found);

    if ($found) return $archives;

    extract(wp_parse_args($args, array(
      'limit'    => -1,
      'order_by' => 'percentage',
      'order'    => 'DESC'
    )));

    $limit_filter = ($limit && $limit != -1)
      ? 'LIMIT ' . (int)$limit
      : '';

    $sql = "
      SELECT *, SUM(percentage) AS total
      FROM (
        SELECT a1.*
        FROM   {$wpdb->prefix}tu_archive a1
        LEFT JOIN (
          SELECT
            user_id,
            test_id,
            MAX(resit_number)   AS resit_number,
            MAX(date_submitted) AS date_submitted
          FROM     {$wpdb->prefix}tu_archive
          GROUP BY user_id, test_id
        ) a2
        ON    (a1.user_id = a2.user_id AND a1.test_id = a2.test_id)
        WHERE 1 = 1
        AND   a1.resit_number   = a2.resit_number
        AND   a1.date_submitted = a2.date_submitted
      ) totals
      GROUP BY user_id
      ORDER BY {$order_by} {$order}
      {$limit_filter}
    ";

    $archives = $wpdb->get_results($sql, ARRAY_A);

    if ($order_by === 'percentage') {
      $archives = rankify($archives);
    }

    wp_cache_set($cache_id, $archives, $cache_grp);

    return $archives;
  }


}
