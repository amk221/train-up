<?php

/**
 * A class to represent a single Test WP_Post
 *
 * @package Train-Up!
 * @subpackage Tests
 */

namespace TU;

class Test extends Post {

  /**
   * $template_file
   *
   * The template file that is used to render Tests.
   *
   * @var string
   *
   * @access protected
   */
  protected $template_file = 'tu_test';

  /**
   * __construct
   *
   * When a Test is instantiated construct the post as normal, then if it is
   * actually active, check the permissions and also listen out for requests
   * to it that are attempting to start, finish or resit this test.
   *
   * @param mixed $post
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($post, $active = false) {
    parent::__construct($post, $active);

    if ($this->is_active()) {
      list($ok, $error) = $this->pre_process();

      if ($ok) {
        add_action('wp_enqueue_scripts', array($this, '_add_assets'));
      }

      $ok or $this->bail($error);
    }
  }

  /**
   * as_tin_can_activity
   *
   * Convert this test to a format that the Tin Can API will understand.
   *
   * @access public
   *
   * @return object
   */
  public function as_tin_can_activity() {
    $version              = tu()->config['tin_can']['version'];
    $activity             = new Tin_can_activity;
    $activity->id         = $this->url;
    $activity->definition = array(
      'type' => "http://adlnet.gov/expapi/activities/assessment",
      'name' => array( get_locale() => $this->post_title )
    );

    return $activity;
  }

  /**
   * get_level_id
   *
   * @access public
   *
   * @return integer The ID of the level that this test belongs to
   */
  public function get_level_id() {
    return get_post_meta($this->ID, 'tu_level_id', true);
  }

  /**
   * set_level_id
   *
   * Set the ID of the level post that this test should belong to
   *
   * @param integer $level_id
   *
   * @access public
   */
  public function set_level_id($level_id) {
    update_post_meta($this->ID, 'tu_level_id', $level_id);
  }

  /**
   * get_level
   *
   * @access public
   *
   * @return object The Level post that this Test belongs to
   */
  public function get_level() {
    return Levels::factory($this->level_id);
  }

  /**
   * get_questions
   *
   * @param array $args
   *
   * @access public
   *
   * @return array The Questions that belong to this Test
   */
  public function get_questions($args = array()) {
    $args = array_merge(array(
      'numberposts' => -1,
      'post_type'   => "tu_question_{$this->ID}",
      'meta_key'    => 'tu_test_id',
      'meta_value'  => $this->ID,
      'orderby'     => 'menu_order',
      'order'       => 'ASC'
    ), $args);

    return get_posts_as('Questions', $args);
  }

  /**
   * get_question_ids
   *
   * @access public
   *
   * @return array The IDs of the Questions associated with this Test.
   */
  public function get_question_ids() {
    $question_ids = array();
    foreach ($this->questions as $question) {
      $question_ids[] = $question->ID;
    }
    return $question_ids;
  }

  /**
   * get_results
   *
   * @param array $args
   *
   * @access public
   *
   * @return array Result posts of Trainees who have taken this Test.
   */
  public function get_results($args = array()) {
    $args = array_merge(array(
      'numberposts' => -1,
      'post_type'   => "tu_result_{$this->ID}",
      'meta_key'    => 'tu_test_id',
      'meta_value'  => $this->ID,
      'orderby'     => 'post_date'
    ), $args);

    return get_posts_as('Results', $args);
  }

  /**
   * get_grades
   *
   * - Attempt to load the grade information that is specific to this test, if
   *   nothing is found, use the default grade information.
   * - Note, we call array_values so that the array keys are reset to be
   *   sequential, because when they are saved the user might have deleted some.
   *
   * @access public
   *
   * @return array
   */
  public function get_grades() {
    $grades = get_post_meta($this->ID, 'tu_grades', true);

    if (!$grades || count($grades) <= 0) {
      $grades = tu()->config['tests']['grades'];
    }

    return array_values($grades);
  }

  /**
   * set_grades
   *
   * - Store array of hashes of grade information for this test, used to
   *   determine what the minimum pass percentage is etc.
   *   { description: 'Pass', percentage: 50% }
   * - Sort the array so it is in order of lowest percentage to highest.
   *   This is so that get_grade_for_percentage can traverse it correctly,
   *   and so it looks better on screen.
   *
   * @param array $grades
   *
   * @access public
   */
  public function set_grades($grades) {
    $sort = function($a, $b) {
      $a = isset($a['percentage']) ? $a['percentage'] : 0;
      $b = isset($b['percentage']) ? $b['percentage'] : 0;

      return $a > $b;
    };

    usort($grades, $sort);

    delete_post_meta($this->ID, 'tu_grades');
    add_post_meta($this->ID, 'tu_grades', $grades);
  }

  /**
   * get_percent_complete
   *
   * Note: Because a Trainee's answers to a particular Test are only stored
   * transiently, we force the percentage complete to be 100% after they've
   * finished the Test.
   *
   * @param object $user
   *
   * @access public
   *
   * @return integer The percentage 'compeleteness' for the given user.
   * (i.e. how many answers they have attempted out of the total)
   */
  public function get_percent_complete($user) {
    $total = count($this->questions);

    $attempted = 0;
    foreach ($this->questions as $question) {
      $attempted += (int)$user->has_answered_question($question);
    }

    $finished = $user->finished_test($this->ID);
    $amount   = $finished ? $total : $attempted;
    $percent  = $total > 0 ? round($amount / $total * 100) : 0;

    return $percent;
  }

  /**
   * _add_assets
   *
   * - Fired on `wp_enqueue_scripts` when this Test is active.
   * - Add the script that deals with the test timer
   * - Fire another action specific to Tests, so that develoeprs can enqueue
   *   scripts and styles on the front end.
   *
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_script('tu_frontend_tests');

    do_action('tu_test_frontend_assets');
  }

  /**
   * started
   *
   * @access public
   *
   * @return boolean Whether or not this test has been started by any users.
   */
  public function started() {
    global $wpdb;

    $sql = "
      SELECT COUNT(user_id)
      FROM   {$wpdb->usermeta}
      WHERE  meta_key   = %s
      OR     meta_key   = %s
      AND    meta_value > 0
    ";

    return (bool)$wpdb->get_var($wpdb->prepare(
      $sql,
      "tu_started_test_{$this->ID}",
      "tu_finished_test_{$this->ID}"
    ));
  }

  /**
   * can_edit
   *
   * Returns whether or not this test can be edited. It can be edited if no
   * users have started it, otherwise it would be unfair.
   *
   * As of 1.1.11 this can be overridden by those desperate to manipulate tests
   * and their questions, even after they have been started. Naughty naughty :/
   *
   * @access public
   *
   * @return boolean
   */
  public function can_edit() {
    return !$this->started();
  }

  /**
   * force_questions_post_status
   *
   * - Fired when a test is saved
   * - Update all the questions within this test to have the same post status
   *   as the test itself. This is because when a test is trashed, the questions
   *   within it should also be trashed etc.
   *
   * @access private
   */
  private function force_questions_post_status() {
    global $wpdb;

    $wpdb->query($wpdb->prepare("
      UPDATE {$wpdb->posts}
      SET    post_status = %s
      WHERE  post_type = %s
    ",
    $this->post_status,
    "tu_question_{$this->ID}"));
  }

  /**
   * clear_meta
   *
   * - A helper to remove meta information that this test has created. This is
   *   basically so that when a test is deleted we can clean up properly
   * - This function is also called when a Test is reset, if you need to alter
   *   clear_meta, consider it may need refactoring into two functions.
   *
   * @access private
   */
  public function clear_meta() {
    global $wpdb;

    $wpdb->query("
      DELETE FROM {$wpdb->usermeta}
      WHERE  meta_key = 'tu_started_test_{$this->ID}'
      OR     meta_key = 'tu_finished_test_{$this->ID}'
    ");
  }

  /**
   * save
   *
   * - When a test is saved, load the level that it belongs to and set the
   *   test's title and menu order to be the same as the level.
   * - Force all questions posts within this test to have the same post status
   *   as the test itself
   * - Set the Test's nesting to be that of a similar structure to its related
   *   Level by settings its post_parent.
   * - Register two new post types, one for this test's questions and one for
   *   it's results.
   *
   * @param array $data
   *
   * @access public
   */
  public function save($data = array()) {
    $level = $this->level;

    parent::save(array_merge(array(
      'post_title'  => $level->post_title,
      'menu_order'  => $level->menu_order,
      'post_parent' => $level->parent && $level->parent->test ? $level->parent->test->ID : null
    ), $data));

    $this->force_questions_post_status();

    if ($this->post_status !== 'auto-draft') {
      $post_type = new Question_post_type($this->ID);
      $post_type->cache();

      $post_type = new Result_post_type($this->ID);
      $post_type->cache();
    }
  }

  /**
   * delete
   *
   * - When a test is deleted, de-register the custom post types that it had.
   * - Delete all the associated questions in this test
   * - Delete all the result posts that were for this test (the results
   *   themselves will still remain in the archive).
   * - Clean up any meta information that this test was using
   * - If it is a hard deletion, then delete the actual WordPress post too.
   *
   * @param boolean $hard Delete the actual post
   *
   * @access public
   */
  public function delete($hard = true) {
    $this->delete_questions();
    $this->delete_results();
    $this->clear_meta();

    $post_type = new Question_post_type($this->ID);
    $post_type->forget();

    $post_type = new Result_post_type($this->ID);
    $post_type->forget();

    if ($hard) {
      parent::delete();
    }
  }

  /**
   * trash
   *
   * When a test is trashed, also trash any questions and results that belong
   * to it
   *
   * @param boolean $hard Delete the actual post
   *
   * @access public
   */
  public function trash($hard = true) {
    $this->trash_questions();
    $this->trash_results();

    if ($hard) {
      parent::trash();
    }
  }

  /**
   * untrash
   *
   * When a test is untrashed, also untrash the questions and results posts that
   * belong to it
   *
   * @param boolean $hard Delete the actual post
   *
   * @access public
   */
  public function untrash($hard = true) {
    $this->untrash_questions();
    $this->untrash_results();

    if ($hard) {
      parent::untrash();
    }
  }

  /**
   * delete_questions
   *
   * @access private
   */
  private function delete_questions() {
    $questions = $this->get_questions(array('post_status' => get_post_stati()));

    foreach ($questions as $question) {
      $question->delete();
    }
  }

  /**
   * trash_questions
   *
   * @access private
   */
  private function trash_questions() {
    $questions = $this->get_questions(array('post_status' => get_post_stati()));

    foreach ($questions as $question) {
      $question->trash();
    }
  }

  /**
   * untrash_questions
   *
   * @access private
   */
  private function untrash_questions() {
    $questions = $this->get_questions(array('post_status' => get_post_stati()));

    foreach ($questions as $question) {
      $question->untrash();
    }
  }

  /**
   * delete_results
   *
   * @access private
   */
  private function delete_results() {
    $results = $this->get_results(array('post_status' => get_post_stati()));

    foreach ($results as $result) {
      $result->delete();
    }
  }

  /**
   * trash_results
   *
   * @access private
   */
  private function trash_results() {
    $results = $this->get_results(array('post_status' => get_post_stati()));

    foreach ($results as $result) {
      $result->trash();
    }
  }

  /**
   * untrash_results
   *
   * @access private
   */
  private function untrash_results() {
    $results = $this->get_results(array('post_status' => get_post_stati()));

    foreach ($results as $result) {
      $result->untrash();
    }
  }

  /**
   * pre_process
   *
   * - Fired before a test is to be viewed
   * - Listen out for attempts to start, finish or resit this test
   * - Optionally redirect to a given url after processing any requested actions
   * - Make sure the currently logged in user has permission to perform
   *   the requested action.
   * - If the user has run out of time, then finish the test for them.
   *
   * @access private
   */
  private function pre_process() {
    $action   = isset($_REQUEST['tu_action'])   ? $_REQUEST['tu_action']   : null;
    $redirect = isset($_REQUEST['tu_redirect']) ? $_REQUEST['tu_redirect'] : null;
    $_test    = strtolower(tu()->config['tests']['single']);

    list($ok, $error, $code) = tu()->user->can_access_test($this);

    if ($code === 0x01) {
      tu()->user->finish_test($this, true);
    }
    else if ($action === 'start') {
      list($ok, $error) = tu()->user->can_start_test($this);

      if ($ok) {
        tu()->user->start_test($this);

        if (count($this->questions) < 1) {
          $ok    = false;
          $error = sprintf(__('No questions found for this %1$s', 'trainup'), $_test);
        } else if ($redirect) {
          go_to($redirect);
        } else {
          $this->questions[0]->go_to();
        }
      }
    }
    else if ($action === 'finish') {
      list($ok, $error) = tu()->user->can_finish_test($this);

      if ($ok) {
        tu()->user->finish_test($this, true);

        if ($redirect) {
          go_to($redirect);
        }
      }
    }
    else if ($action === 'resit') {
      list($ok, $error) = tu()->user->can_resit_test($this);

      if ($ok) {
        tu()->user->resit_test($this);
      }
    }

    return array($ok, $error);
  }

  /**
   * get_featured_image
   *
   * A Test's featured image is taken from the Level it is in.
   *
   * @access public
   *
   * @return string The URL of the image
   */
  public function get_featured_image() {
    return $this->level->featured_image;
  }

  /**
   * get_breadcrumb_trail
   *
   * Return the array of breadcrumbs for this Test. Let them be filtered
   * so that developers can customise them.
   *
   * @access public
   *
   * @return array
   */
  public function get_breadcrumb_trail() {
    $crumbs = $this->level->breadcrumb_trail;

    $crumbs[] = array(
      'url'   => $this->url,
      'title' => __('Test', 'trainup')
    );

    $result = apply_filters('tu_test_crumbs', $crumbs);

    return $result;
  }

  /**
   * calculate_results
   *
   * - Generate a hash that contains basic result information about the given
   *   user's attempt at this test.
   * - Also generate a sub hash 'answers' that contains a bit more detail about
   *   the questions they took and whether or not they were right or wrong.
   *
   * @param object $user
   *
   * @access public
   *
   * @return array
   */
  public function calculate_results($user) {
    $out_of  = count($this->questions);
    $mark    = 0;
    $answers = array();

    foreach ($this->questions as $question) {
      $correct = Questions::validate_answer($user, $question);

      if ($correct) {
        $mark += 1;
      }

      $answers[] = array(
        'correct'     => $correct,
        'question'    => $question->get_title(false, 0),
        'question_id' => $question->ID,
        'answer'      => $user->get_answer_to_question($question->ID)
      );
    }

    $percentage = $mark > 0 ? round($mark / $out_of * 100) : 0;

    list($grade, $passed) = $this->get_grade_for_percentage($percentage);

    return compact('mark', 'out_of', 'percentage', 'grade', 'passed', 'answers');
  }

  /**
   * get_grade_for_percentage
   *
   * Accept a percentage and return what grade is achieved for that percentage,
   * when taking this test. (Remember, each test may have its own grade rules).
   *
   * @param integer $percentage
   *
   * @access public
   *
   * @return array List of Grade description, and a passed flag
   */
  public function get_grade_for_percentage($percentage) {
    return Tests::get_grade_for_percentage($percentage, $this->grades);
  }

  /**
   * set_resit_attempts
   *
   * Assign a number of available resit attempts to this test.
   *
   * @param integer $resit_attempts
   *
   * @access public
   */
  public function set_resit_attempts($resit_attempts) {
    update_post_meta($this->ID, 'tu_resit_attempts', $resit_attempts);
  }

  /**
   * get_resit_attempts
   *
   * @access public
   *
   * @return integer The amount of times a trainee can resit this test
   */
  public function get_resit_attempts() {
    return get_post_meta($this->ID, 'tu_resit_attempts', true) ?: 0;
  }

  /**
   * is_resitable
   *
   * @see can_resit_test in base/user.php
   *
   * @access public
   *
   * @return boolean Whether or not this test is resitable.
   */
  public function is_resitable() {
    return $this->resit_attempts > 0;
  }

  /**
   * set_time_limit
   *
   * Limit the time a Trainee has to take this Test by assigning it hours and
   * minutes HH:MM.
   *
   * @param string $time_limit
   *
   * @access public
   */
  public function set_time_limit($time_limit) {
    update_post_meta($this->ID, 'tu_time_limit', $time_limit);
  }

  /**
   * get_time_limit
   *
   * @access public
   *
   * @return string|null Hours and minutes HH:MM
   */
  public function get_time_limit() {
    return get_post_meta($this->ID, 'tu_time_limit', true) ?: '00:00:00';
  }

  /**
   * has_time_limit
   *
   * @access public
   *
   * @return boolean Whether or not this test actually has a time limit
   */
  public function has_time_limit() {
    return !preg_match("/00:00(:00)?/", $this->time_limit);
  }

  /**
   * set_result_status
   *
   * Configures how this Test's Results are published after a Trainee submits
   * their answers. (The default is to be published, but administrators or
   * Group Managers may wish to fiddle the results).
   *
   * @param string Post status
   *
   * @access public
   */
  public function set_result_status($post_status) {
    update_post_meta($this->ID, 'tu_result_status', $post_status);
  }

  /**
   * get_result_status
   *
   * @access public
   *
   * @return string The post status for new Results to this Test
   */
  public function get_result_status() {
    $post_status = get_post_meta($this->ID, 'tu_result_status', true);

    if (!$post_status) {
      $post_status = tu()->config['tests']['default_result_status'];
    }

    return $post_status;
  }

  /**
   * get_end_time
   *
   * The end time is the time the Trainee started the test, plus the amount
   * of time available to them. If they've not started the test, then now
   * is used as the start time.
   *
   * Note: Originally we didn't support seconds, only HH:MM
   * Which is why below we make sure the array has the default seconds if not
   * actually supplied.
   *
   * @param object $user
   *
   * @access public
   *
   * @return object The time that this Test will end for the given user.
   */
  public function get_end_time($user) {
    $time  = explode(':', $this->get_time_limit());

    if (count($time) === 2) {
      $time[] = '00';
    }

    list($hours, $mins, $secs) = $time;

    $started    = $user->started_test($this->ID) ?: time();
    $start_time = new \DateTime;
    $start_time = $start_time->setTimestamp($started);
    $spec       = "PT{$hours}H{$mins}M{$secs}S";
    $time_limit = new \DateInterval($spec);

    return $start_time->add($time_limit);
  }

  /**
   * get_time_remaining
   *
   * We get the difference between the end time of the Test and Now. This may
   * however return a positive time interval even after the Test has finished
   * and so we inspect the `invert` property which will tell us whether or not
   * the time has 'gone past' the allow time.
   *
   * @param object $user
   *
   * @access public
   *
   * @return object Amount of time left the given user has to take this Test.
   */
  public function get_time_remaining($user) {
    $now       = new \DateTime();
    $end_time  = $this->get_end_time($user);
    $remaining = $end_time->diff($now);

    if (!$remaining->invert) { // No time left
      $remaining = new \DateInterval('PT0H');
    }

    return $remaining;
  }

  /**
   * get_active_trainee_ids
   *
   * Get the IDs of Trainees who have started this test, but not finished it.
   *
   * @access public
   *
   * @return array $user_ids
   */
  public function get_active_trainee_ids() {
    global $wpdb;

    $sql = "
      SELECT m1.user_id AS user_id
      FROM {$wpdb->usermeta} m1
      JOIN {$wpdb->usermeta} m2
      ON m1.user_id = m2.user_id
      WHERE m1.meta_key = 'tu_started_test_{$this->ID}'
      AND (
        m2.meta_key = '{$wpdb->prefix}capabilities' AND
        m2.meta_value LIKE '%trainee%'
      )
    ";

    $trainee_ids = array();

    foreach ($wpdb->get_results($sql) as $row) {
      $trainee_ids[] = $row->user_id;
    }

    return $trainee_ids;
  }

  /**
   * delete_redundant_files
   *
   * Goes through all the files uploaded to this test's questions and deletes
   * all but the most recent. (Discarding old attempts at the test). This is
   * mostly used just to save space.
   *
   * @access public
   *
   * @return array Files deleted, and files remaining and space saved
   */
  public function delete_redundant_files() {
    return Tests::delete_redundant_files($this);
  }

  /**
   * reset
   *
   * - Deletes the answers (and files!) for all the Trainees who are currently
   *   taking this test.
   * - It will also delete the flags that say whether a Trainee has started/
   *   finished the test (so they have to start it again)
   * - It also deletes any results of Trainees who have already finished it
   * - The overall effect being this Test will be editable once more
   *
   * @access public
   */
  public function reset() {
    foreach ($this->questions as $question) {
      $question->clear_temporary_answers();
      $question->delete_files();
    }
    foreach ($this->results as $result) {
      $result->delete();
    }

    $this->clear_meta();
  }

  /**
   * get_archives
   *
   * - Select rows from the DB archive for each user who took this
   *   test, but only get their latest attempt at this test (the max resit).
   * - If a group ID is supplied, then filter the archive by the IDs of the
   *   Trainees who are in that group.
   * - If the currently logged in user is a Group manager, then *further*
   *   filter the Trainee IDs to only be ones which they are allowed to see.
   * - Note: Unlike User archives, Test archives show users latest results for a
   *   particular test rather than all attempts for a particular test. Therefore
   *   this data is rankable as we can compare each user's result.
   * - If the user specifies the order to be by percentage, then we can
   *   add an extra column `rank`. It has to be 'percentage' rather than mark
   *   because percentage is user-customisable, sometimes rendering mark void.
   *
   * @param array $args
   *
   * @access public
   *
   * @return array
   */
  public function get_archives($args = array()) {
    global $wpdb;

    $cache_grp = 'tu_test_archives';
    $cache_id  = md5($this->ID.serialize(func_get_args()));
    $archives  = wp_cache_get($cache_id, $cache_grp, false, $found);

    if ($found) return $archives;

    extract(wp_parse_args($args, array(
      'limit'       => -1,
      'trainee_ids' => array(),
      'group_id'    => null,
      'order_by'    => 'date_submitted',
      'order'       => 'ASC'
    )));

    if ($group_id) {
      $trainee_ids = Groups::factory($group_id)->trainee_ids;
    }

    if (tu()->user->is_group_manager()) {
      $allowed     = tu()->group_manager->access_trainee_ids;
      $trainee_ids = filter_ids($allowed, $trainee_ids);
    }

    $trainee_filter = (count($trainee_ids) > 0)
      ? 'AND a1.user_id IN ('.join(',', $trainee_ids).')'
      : '';

    $limit_filter = ($limit && $limit != -1)
      ? 'LIMIT ' . (int)$limit
      : '';

    $sql = "
      SELECT a1.*, a2.resit_number
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
      WHERE a1.test_id = %d
      AND   a1.resit_number   = a2.resit_number
      AND   a1.date_submitted = a2.date_submitted
      {$trainee_filter}
      ORDER BY a1.{$order_by} {$order}
      {$limit_filter}
    ";

    $archives = $wpdb->get_results($wpdb->prepare($sql, $this->ID), ARRAY_A);

    if ($order_by === 'percentage') {
      $archives = rankify($archives);
    }

    wp_cache_set($cache_id, $archives, $cache_grp);

    return $archives;
  }

  /**
   * get_group_performance_data
   *
   * - Find groups that are eligible for taking this test. Go through the
   *   archived results for this test and count up the scores for each group.
   * - This will find the average score of the users who took this test
   *   it will not produce accurate/fair results if a different amount of
   *   Trainees have taken the test in each group.
   * - This could be re-factored in to straight SQL if it becomes a sticking
   *   point in terms of speed.
   * - Bare in mind that Trainees can be in more than one group.
   *
   * @access public
   *
   * @return array
   */
  public function get_group_performance_data() {
    $_group   = tu()->config['groups']['single'];
    $_trainee = strtolower(tu()->config['trainees']['single']);
    $_stat    = sprintf(__('Average %1$s performance', 'trainup'), $_trainee);
    $_id      = __('ID', 'trainup');
    $_colour  = __('Colour', 'trainup');
    $data     = array(array($_group, $_id, $_stat, $_colour));
    $groups   = array();

    foreach ($this->level->groups as $group) {
      $groups[$group->ID] = array(
        'post_title'   => $group->post_title,
        'ID'           => $group->ID,
        'colour'       => $group->colour,
        'total_marks'  => 0,
        'total_out_of' => 0,
        'trainees'     => 0
      );
    }

    foreach ($this->archives as $archive) {
      $user = Users::factory($archive['user_id']);

      if (!$user->loaded()) {
        // The Trainee has since been deleted from the system
        continue;
      }

      foreach ($user->get_group_ids() as $group_id) {
        if (!isset($groups[$group_id])) {
          // The Trainee happens to be in a Group that isn't relevant for this Test
          continue;
        }

        $groups[$group_id]['total_marks']  += $archive['mark'];
        $groups[$group_id]['total_out_of'] += $archive['out_of'];
        $groups[$group_id]['trainees']     += 1;
      }
    }

    foreach ($groups as $group_id => $group) {
      if (!$group['trainees']) {
        // No Trainees in that group have taken this test yet
        continue;
      }

      $average = 0;

      if ($group['total_marks'] > 0) {
        $average = round($group['total_marks'] / $group['total_out_of'] * 100);
      }

      $row = array(
        $group['post_title'],
        $group['ID'],
        $average,
        $group['colour']
      );

      $data[] = $row;
    }

    return $data;
  }

}



