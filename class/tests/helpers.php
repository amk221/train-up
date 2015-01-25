<?php

/**
 * General helper functions for working with Tests
 *
 * @package Train-Up!
 * @subpackage Tests
 */

namespace TU;

class Tests {

  /**
   * factory
   *
   * @param array|object $test
   *
   * @access public
   * @static
   *
   * @return object A Test instance
   */
  public static function factory($test = null) {
    return new Test($test);
  }

  /**
   * find_all
   *
   * @param array $args
   *
   * @access public
   * @static
   *
   * @return array Tests that match the args
   */
  public static function find_all($args = array()) {
    return get_posts_as('Tests', array_merge(array(
      'numberposts' => -1,
      'post_type'   => 'tu_test',
      'orderby'     => 'menu_order',
      'order'       => 'ASC'
    ), $args));
  }

  /**
   * _filter
   *
   * - Fired on `pre_get_posts` (for tests in admin), and `tu_pre_get_tests`.
   * - Limit the tests to ones which are assigned to a Group which the current
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
      $ids = filter_ids(tu()->group_manager->access_test_ids, $query->query_vars['post__in']);
      $query->set('post__in', $ids);
    }
    return $query;
  }

  /**
   * get_grade_for_percentage
   *
   * - Accept a percentage and a grade configuration hash. Loop through each one
   *   and return the appropriate grade for the given percentage.
   * - If the percentage is higher than, or equal to the grades percentage
   *   then it must be a pass.
   *
   * @param integer $percentage
   * @param array $grades
   *
   * @access public
   * @static
   *
   * @return array List of Grade description, and a passed flag
   */
  public static function get_grade_for_percentage($percentage, $grades) {
    $description = $grades[0]['description'];
    $passed      = 0;

    for ($i = count($grades) - 1; $i >= 1; $i--) {
      $grade = $grades[$i];

      if ($percentage >= $grade['percentage']) {
        $description = $grade['description'];
        $passed      = 1;
        break;
      }
    }

    return array($description, $passed);
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
    $_test     = strtolower(tu()->config['tests']['single']);
    $_trainees = strtolower(tu()->config['trainees']['plural']);

    return array(
      '_confirmDeleteTest'     => self::confirm_delete_test_msg(),
      '_confirmDeleteQuestion' => __('Are you sure you want to delete this question?', 'trainup') . "\n\n" . __('Doing so will also delete any files uploaded in response to it.', 'trainup'),
      '_confirmResetTest'      => sprintf(__('Resetting a %1$s will delete the answers of any %2$s who are currently taking this %1$s. (Meaning they will have to start again!).%3$sIt will also allow %2$s who have already taken this test to take it again without it being an official re-sit.%3$sAre you sure you want to continue?', 'trainup'), $_test, $_trainees, "\n\n")
    );
  }

  /**
   * localised_frontend_js
   *
   * - If a Test is available, return the localised JavaScript.
   *   We include some timestamp data for the countdown clock.
   * - Filterable so developers can customise the TU_TESTS JS namespace
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_frontend_js() {
    $test = isset(tu()->test) ? tu()->test :
      ( isset(tu()->question) ? tu()->question->test : null );

    if (!$test) return;

    $js = array(
      'timeLimit'       => $test->time_limit,
      'startTime'       => tu()->user->started_test($test->ID),
      'endTime'         => $test->get_end_time(tu()->user)->getTimeStamp(),
      'finishTestUrl'   =>  add_query_arg(array('tu_action' => 'finish'), $test->url),
      '_timeUp'          => __('You have run out of time!', 'trainup'),
      '_finishTestCheck' => __("Are you sure you want to submit your answers?\n(You cannot undo this action)", 'trainup')
    );

    $result = apply_filters('tu_tests_js_namespace', $js);

    return $result;
  }

  /**
   * confirm_delete_test_msg
   *
   * @access private
   * @static
   *
   * @return string
   */
  private static function confirm_delete_test_msg() {
    $msg = sprintf(
      __('Are you sure you want to delete this %1$s?', 'trainup'),
      strtolower(tu()->config['tests']['single'])
    );
    $msg .= "\n\n";
    $msg .= __('Doing so will also delete the related results and uploaded files.', 'trainup');
    return $msg;
  }

  /**
   * localised_grades_js
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_grades_js() {
    return array(
      '_fail' => __('Unsuccessful', 'trainup'),
      '_pass' => __('Pass', 'trainup')
    );
  }

  /**
   * delete_redundant_files
   *
   * Goes through all the files uploaded to all test's questions and deletes
   * all but the most recent files. (Discarding old attempts at the test). This
   * is mostly used just to save space. Optionally accept a test to limit
   * the deletion to only its Questions.
   *
   * @access public
   *
   * @return array of statistics.
   */
  public static function delete_redundant_files($test = null) {
    $dir             = wp_upload_dir();
    $slug            = tu()->get_slug();
    $path            = "{$dir['basedir']}/{$slug}";
    $files_deleted   = 0;
    $files_remaining = 0;
    $space_saved     = 0;
    $space_taken     = 0;

    $user_paths = (array)glob("{$path}/*", GLOB_ONLYDIR);

    foreach ($user_paths as $user_path) {

      $question_paths = (array)glob("{$user_path}/*", GLOB_ONLYDIR);

      foreach ($question_paths as $question_path) {
        $question_id = basename($question_path);

        if ($test && !in_array($question_id, $test->question_ids)) continue;

        $attempt_paths = (array)glob("{$question_path}/*", GLOB_ONLYDIR);
        $attempt_nums  = array();

        foreach ($attempt_paths as $attempt_path) {
          $attempt_nums[] = basename($attempt_path);
        }

        sort($attempt_nums);
        $latest_attempt_num = max($attempt_nums);

        foreach ($attempt_paths as $attempt_path) {
          $attempt_num = basename($attempt_path);

          if ($attempt_num == $latest_attempt_num) continue;

          $file_paths = (array)glob("{$attempt_path}/*");

          foreach ($file_paths as $file_path) {
            $space_saved += filesize($file_path);
            $files_deleted++;
            unlink($file_path);
          }

          rmdir($attempt_path);
        }

        $file_paths = (array)glob("{$question_path}/{$latest_attempt_num}/*");

        foreach ($file_paths as $file_path) {
          $space_taken += filesize($file_path);
          $files_remaining++;
        }
      }
    }

    return array($files_deleted, $space_saved, $files_remaining, $space_taken);
  }

  /**
   * deletion_message
   *
   * @param mixed $info An array of data about the deletion process from
   * delete_redundant_files()
   *
   * @access public
   * @static
   *
   * @return Message usually shown as a flash
   */
  public static function deletion_message($info) {
    list(
      $files_deleted,
      $space_saved,
      $files_remaining,
      $space_taken
    ) = $info;

    $deleted   = _n('%1$s old file was deleted', '%1$s old files were deleted', $files_deleted, 'trainup');
    $remaining = _n('%2$s file remains', '%2$s files remain', $files_remaining, 'trainup');
    $saved     = size_format($space_saved, 2);
    $saved     = $space_saved ? sprintf(__(' (saving %1$s)', 'trainup'), $saved) : '';
    $taken     = size_format($space_taken, 2);
    $taken     = $space_taken ? sprintf(__(' (consuming %1$s)', 'trainup'), $taken) : '';
    $message   = "{$deleted}{$saved}. {$remaining}{$taken}.";

    return sprintf($message, $files_deleted, $files_remaining);
  }

}
