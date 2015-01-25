<?php

/**
 * Abstract class to wrap WP_User objects
 *
 * @package Train-Up!
 */

namespace TU;

class User {

  /**
   * $user
   *
   * The actual WP_User object that this class wraps.
   *
   * @var WP_User
   *
   * @access public
   */
  public $user;

  /**
   * __construct
   *
   * Create a new user
   *
   * - If arg is a user, just wrap it.
   * - If arg is a user ID, instantiate a new user, and wrap it
   * - If arg is a string, load it by email
   * - If arg is a hash, populate a new user
   *
   * @param mixed $user
   *
   * @access public
   */
  public function __construct($user) {
    if (is_object($user) && is_a($user, 'WP_User')) {
      $this->user = $user;
    } else if (is_numeric($user)) {
      $this->user = get_user_by('id', $user) ?: new \WP_User;
    } else if (is_string($user) && stristr($user, '@')) {
      $this->user = get_user_by('email', $user) ?: new \WP_User;
    } else {
      $this->user = new \WP_User;
      $this->set_data($user);
    }
  }

  /**
   * as_tin_can_actor
   *
   * Convert this user to a format that the Tin Can API will understand.
   *
   * @access public
   *
   * @return mixed Value.
   */
  public function as_tin_can_actor() {
    $actor = new Tin_can_actor;
    $actor->name = $this->display_name;
    $actor->mbox = "mailto:{$this->user_email}";

    return $actor;
  }

  /**
   * set_data
   *
   * Update this instance with the properties provided. This is mostly for
   * trickling data down into the user object that this class wraps.
   *
   * @param array $data
   *
   * @access private
   */
  private function set_data($data = array()) {
    foreach ((object)$data as $property => $value) {
      $this->$property = $value;
    }
  }

  /**
   * get_data
   *
   * Make sure the ID is returned with the user is converted to an array.
   * It sometimes isn't, because bizarrely, in WP_User, it is not in `$data`.
   *
   * @access private
   *
   * @return array The important keys/values that correspond to db columns/cells
   */
  private function get_data() {
    return array_merge(array('ID' => $this->ID), $this->user->to_array());
  }

  /**
   * get_all_data
   *
   * WordPress stores the first and last name, and other fields separately.
   * So, here we provide access to a nice hash of 'all' the data, suitable
   * for swaps.
   *
   * @access public
   *
   * @return array
   */
  public function get_all_data() {
    $data = $this->get_data();

    foreach (_get_additional_user_keys($this->user) as $key) {
      $data[$key] = get_user_meta($this->ID, $key, true);
    }

    return $data;
  }

  /**
   * save
   *
   * - Inserts or updates the user accordingly
   * - First, update this instance with the new properties
   * - Then, always set the `user_nicename` because this makes users more
   *   searchable.
   * - If saving for the first time, re-load the user to populate potential
   *   missing data, like display_name and user_pass etc...
   *
   * @param array $data A hash of properties to update
   *
   * @access public
   */
  public function save($data = array()) {
    $this->set_data($data);

    $this->user_nicename = trim($this->first_name.' '.$this->last_name);

    $new_data = $this->get_data();

    if ($this->ID) {
      wp_update_user($new_data);
    } else {
      $this->ID = wp_insert_user($this->user);
      $this->user = get_user_by('id', $this->ID);
    }
  }

  /**
   * assign_key
   *
   * The `user_activation_key` field doesn't get saved with `wp_update_user`
   * So this function allows to assign an activation key to a user.
   *
   * @access public
   */
  public function assign_key() {
    global $wpdb;

    $this->user_activation_key = wp_generate_password(32, false);

    $wpdb->update(
      $wpdb->users,
      array('user_activation_key' => $this->user_activation_key),
      array('ID' => $this->ID)
    );
  }

  /**
   * clear_key
   *
   * Remove this user's activation key, thereby activating their account.
   *
   * @access public
   */
  public function clear_key() {
    global $wpdb;

    $this->user_activation_key = '';

    $wpdb->update(
      $wpdb->users,
      array('user_activation_key' => ''),
      array('ID' => $this->ID)
    );
  }

  /**
   * loaded
   *
   * A user is considered loaded if its user_login property has been
   * populated. This is because the model lets us load by ID or email and
   * to have a user_login property implies it was found in the DB.
   *
   * @access public
   *
   * @return boolean
   */
  public function loaded() {
    return $this->ID && $this->user_login;
  }

  /**
   * delete
   *
   * - Delete this user.
   * - When a user is deleted, (one of the plugin's users anyway) we also want
   *   to clean up a bit and remove stuff no longer needed.
   *
   * @param boolean $hard Delete the actual user
   *
   * @access public
   */
  public function delete($hard = true) {
    $this->delete_results();

    if ($hard) {
      wp_delete_user($this->ID);
    }
  }

  /**
   * __call
   *
   * Provide a shortcut to methods on the WordPress User that this class wraps
   *
   * @param
   *
   * @access public
   *
   * @return mixed
   */
  public function __call($method, $args = array()) {
    return call_user_func_array(array($this->user, $method), $args);
  }

  /**
   * __get
   *
   * Provide a shortcut to get the user's details
   *
   * @param string $property
   *
   * @access public
   *
   * @return mixed
   */
  public function __get($property) {
    $getter = "get_{$property}";
    if (method_exists($this, $getter)) {
      return $this->$getter();
    }

    return $this->user->$property;
  }

  /**
   * __set
   *
   * - Provide a shortcut to set the user's details that this class wraps.
   * - Suppresses error from WordPress capabilities.php which does not provide
   *   a default `$data` object on its User Class, resulting in this error:
   *   "Creating default object from empty value".
   *
   * @param string $property
   * @param mixed $value
   *
   * @access public
   */
  public function __set($property, $value) {
    if (!isset($this->user->data)) {
      $this->user->data = (object)array();
    }

    $this->user->$property = $value;
  }

  /**
   * start_test
   *
   * - Add a marker for this user that notifies us that they have
   *   started a test, but may not have finished it yet.
   * - If Tin Can is enabled, and tracking of the starting of tests is switched
   *   on, then record a new Statement that this user has started the test.
   *
   * @param object $test
   *
   * @access public
   */
  public function start_test($test) {
    add_user_meta($this->ID, "tu_started_test_{$test->ID}", time(), true);

    tu()->tin_can->start_test($this, $test);
  }

  /**
   * finish_test
   *
   * - Fired when a user wants to finish a test.
   * - Archive the attempt
   * - Create a result post
   * - Track the Tin Can Statement
   * - Note: The finish time is set first so the archiving can take place
   *   (the start and finish times must both be set), then the start time
   *   is cleared lastly.
   * - Fire an action so that developers can listen for succesful completion
   *   of a Test.
   *
   * @param object $test
   * @param bool $redirect Whether or not to redirect to the test result
   *
   * @access public
   */
  public function finish_test($test, $redirect = false) {
    add_user_meta($this->ID, "tu_finished_test_{$test->ID}", time(), true);

    $this->archive_submission($test);
    $result = Results::create($test, $this);
    tu()->tin_can->finish_test($this, $test, $result);

    delete_user_meta($this->ID, "tu_started_test_{$test->ID}");

    do_action('tu_finished_test', $test, $this, $result);

    if ($redirect && $result->post_status === 'publish') {
      $result->go_to();
    }
  }

  /**
   * can_start_test
   *
   * A user can start a test if they have access to it (Group wise), and
   * they've not already started it, and they've not already finished it.
   *
   * @param object $test
   *
   * @access public
   *
   * @return boolean
   */
  public function can_start_test($test) {
    list($ok, $error) = $this->can_access_test($test);

    $_test = tu()->config['tests']['single'];

    if ($this->started_test($test->ID)) {
      $error = sprintf(__('%1$s already started', 'trainup'), $_test);
    } else if ($this->finished_test($test->ID)) {
      $error = sprintf(__('%1$s already finished', 'trainup'), $_test);
    }

    $result = array(!$error, $error);
    $result = apply_filters('tu_user_can_start_test', $result, $this, $test);

    return $result;
  }

  /**
   * can_finish_test
   *
   * In order for a user to be able to finish a test, they must have started it,
   * and not finished it. i.e. They should not be able to submit their answers
   * more than once.
   *
   * @param object $test
   *
   * @access public
   *
   * @return boolean
   */
  public function can_finish_test($test) {
    list($ok, $error) = $this->can_access_test($test);

    if (!$this->taking_test($test->ID)) {
      $error = sprintf(
        __('You cannot finish this %1$s, because you have not started it', 'trainup'),
        strtolower(tu()->config['tests']['single'])
      );
    }

    $result = array(!$error, $error);
    $result = apply_filters('tu_user_can_finish_test', $result, $this, $test);

    return $result;
  }

  /**
   * resit_test
   *
   * - Fired when a user wishes to resit a test.
   * - Remove any markers defining the user's current status against the test,
   *   i.e. It may be marked as 'finished' (from the previous attempt). So, we
   *   remove that enabling them to start it again.
   * - Delete their results, (remember, their archive will remain).
   *   so that a fresh result is computed when this resit is finished.
   * - Increase their attempt count.
   *
   * Note: the above only happens the first time the user attempts to resit
   * the test, because they may access the resit test link more than once,
   * however we don't want to accidentally increase their resat-count each time.
   *
   * @param object $test
   *
   * @access public
   */
  public function resit_test($test) {
    $result = $this->get_result($test->ID);

    delete_user_meta($this->ID, "tu_finished_test_{$test->ID}");

    if ($result) {
      $result->delete();
      $this->resat_test($test->ID);
    }
  }

  /**
   * archive_submission
   *
   * - Accept a test, compile some information about the user's attempt at
   *   taking the test. Store it in the archive. Now the user has finally
   *   finished the test, we can clear their answer sheet, ready for any
   *   possible resits.
   * - Note: When running the insert, die if there is an error this is because
   *   the plugin is relient on archive data, and if it doesn't go in then
   *   the developer needs to know. Some developers have had encoding issues.
   *
   * @param object $test
   *
   * @access private
   */
  private function archive_submission($test) {
    global $wpdb;

    $results  = $test->calculate_results($this);
    $duration = $this->get_duration_for_test($test->ID);
    $attempt  = $this->get_resit_attempts($test->ID);

    $results['answers'] = serialize($results['answers']);

    $archive = array_merge($results, array(
      'date_submitted' => current_time('mysql'),
      'duration'       => $duration,
      'user_id'        => $this->ID,
      'test_id'        => $test->ID,
      'user_name'      => $this->display_name,
      'test_title'     => $test->post_title,
      'resit_number'   => 0
    ));

    if ($attempt > 0) {
      $archive['resit_number'] = $attempt;
    }

    $this->clear_temporary_answers($test->ID);

    $wpdb->insert("{$wpdb->prefix}tu_archive", $archive);

    if ($wpdb->last_error) {
      wp_die($wpdb->last_error);
    }
  }

  /**
   * has_time_left
   *
   * @param object $test
   *
   * @access public
   *
   * @return boolean Whether this user has any time remaining to take the
   * given test.
   */
  public function has_time_left($test) {
    $now          = new \DateTime();
    $end_time     = $test->get_end_time($this);
    $seconds_left = $end_time->getTimeStamp() - $now->getTimeStamp();

    return $test->has_time_limit() ? $seconds_left > 0 : true;
  }

  /**
   * get_duration_for_test
   *
   * Calculate the difference between when the Trainee started the Test and
   * when they finished, to get the time taken.
   *
   * @param object $test
   *
   * @access public
   *
   * @return integer Timestamp
   */
  public function get_duration_for_test($test_id) {
    return $this->finished_test($test_id) - $this->started_test($test_id);
  }

  /**
   * delete_results
   *
   * Remove this user's test results
   *
   * @access public
   */
  public function delete_results() {
    $results = $this->get_results(array('post_status' => get_post_stati()));

    foreach ($results as $result) {
      $result->delete();
    }
  }

  /**
   * get_archives
   *
   * - Select the rows in the DB archive for this user.
   * - Join on the another table with the max resits, so we can figure out
   *   whether the archive entry is for the latest resit. (If it is, then it
   *   will of course have an associated Result).
   * - Optionally accept an array of test IDs to get the archives for, this is
   *   so we can ask for the Trainee's latest results for certain Tests.
   * - If the currently logged in user is a Group manager, then filter these
   *   archives to only be ones which they are allowed to see.
   * - Note: Unlike Test archives, User archives who all attempts at tests,
   *   whereas Test archives show users latest attempt at a test. Hence why this
   *   data does not contain rank information.
   *
   * @param array $args
   *
   * @access public
   *
   * @return array
   */
  public function get_archives($args = array()) {
    global $wpdb;

    $cache_grp = 'tu_user_archives';
    $cache_id  = md5($this->ID.serialize(func_get_args()));
    $archives  = wp_cache_get($cache_id, $cache_grp, false, $found);

    if ($found) return $archives;

    extract(wp_parse_args($args, array(
      'test_ids' => array(),
      'order_by' => 'date_submitted',
      'order'    => 'ASC'
    )));

    if (tu()->user->is_group_manager()) {
      $test_ids = filter_ids(tu()->group_manager->access_test_ids, $test_ids);
    }

    $test_filter = (count($test_ids) > 0)
      ? "AND a1.test_id IN (".join(',', $test_ids).")"
      : '';

    $sql = "
      SELECT a1.*, IF(a1.resit_number = a2.resit_number, 1, 0) as latest
      FROM   {$wpdb->prefix}tu_archive a1
      LEFT JOIN (
        SELECT   user_id, MAX(resit_number) AS resit_number, test_id
        FROM     {$wpdb->prefix}tu_archive
        GROUP BY user_id, test_id
      ) a2
      ON    (a1.user_id = a2.user_id AND a1.test_id = a2.test_id)
      WHERE a1.user_id = %d
      {$test_filter}
      ORDER BY a1.{$order_by} {$order}
    ";

    $archives = $wpdb->get_results($wpdb->prepare($sql, $this->ID), ARRAY_A);

    wp_cache_set($cache_id, $archives, $cache_grp);

    return $archives;
  }

  /**
   * get_archive
   *
   * - Return the row from the DB archive for this user for a given test.
   * - Optionally accept a resit number to limit it to. By default the most
   *   recent resit is returned.
   *
   * @param integer $test_id
   * @param integer $resit_number
   *
   * @access public
   *
   * @return array
   */
  public function get_archive($test_id, $resit_number = null) {
    global $wpdb;

    $cache_grp = 'tu_user_archive';
    $cache_id  = md5($this->ID.serialize(func_get_args()));
    $archive   = wp_cache_get($cache_id, $cache_grp, false, $found);

    if ($found) return $archive;

    $resit_filter = is_null($resit_number)
      ? "ORDER BY resit_number DESC, date_submitted DESC LIMIT 1"
      : "AND resit_number = {$resit_number}";

    $sql = "
      SELECT *
      FROM   {$wpdb->prefix}tu_archive
      WHERE  user_id = %d
      AND    test_id = %d
      {$resit_filter}
    ";

    $archive = $wpdb->get_row($wpdb->prepare($sql, $this->ID, $test_id), ARRAY_A);

    wp_cache_set($cache_id, $archive, $cache_grp);

    return $archive;
  }

  /**
   * get_augmented_archive
   *
   * This is the same as get_archive, as in - it gets the row from the DB
   * that represents a users attempt at a given test. The difference is that
   * we add some more meta information, like related uploaded files other bits
   * that help rendering a view of this data a bit easier. This is probably the
   * function you want.
   *
   * If viewing a Question or a Test Result, mask the download URL to attached
   * files so that Trainees can't easily change the URL to view other trainee's
   * coursework. They still can accesss them with some insider knowledge :/
   *
   * FYI $qid might not be set, because we only started recording the ID of the
   * question being answered in version 1.1.14
   *
   * @param mixed $test_id
   * @param mixed $resit_number
   *
   * @access public
   *
   * @return array
   */
  public function get_augmented_archive($test_id, $resit_number = null) {
    $archive = $this->get_archive($test_id, $resit_number);
    $archive['answers'] = unserialize($archive['answers']) ?: array();
    $slug = tu()->get_slug();
    $uid = $archive['user_id'];
    $dir = wp_upload_dir();

    foreach ($archive['answers'] as $i => &$attempt) {
      $answer = $attempt['answer'];
      $qid    = isset($attempt['question_id']) ? $attempt['question_id'] : null;

      if ($qid) {
        $num    = is_null($resit_number) ? $this->get_resit_attempts($test_id) : $resit_number;
        $unique = "/{$slug}/{$uid}/{$qid}/{$num}";
        $attempt['files'] = (array)glob("{$dir['basedir']}{$unique}/*");

        foreach ($attempt['files'] as &$file) {
          $path = $file;
          $name = basename($path);
          $size = filesize($path);
          if (tu()->in_frontend() && (isset(tu()->question) || isset(tu()->result))) {
            $args = array(
              'tu_action' => 'download_file',
              'tu_file'   => urlencode($name)
            );
            if (!isset(tu()->question)) {
              $args['tu_question_id'] = $qid;
            }
            $url = add_query_arg($args, tu()->post->url);
          } else {
            $url = "{$dir['baseurl']}{$unique}/{$name}";
          }
          $file = compact('path', 'name', 'url', 'size');
        }
      }

      $is_hash = (
        ( is_array($answer) &&
          array_keys($answer) !== range(0, count($answer) - 1) ) ||
        is_object($answer)
      );

      if ($is_hash)
        $type = 'hash';
      if (is_array($answer) && !$is_hash)
        $type = 'array';
      if (is_string($answer) || is_object($answer) && method_exists($answer, '__toString'))
        $type = 'string';
      if (isset($attempt['files']) && count($attempt['files']) > 0)
        $type = 'files';

      $attempt['type'] = $type;
    }

    return $archive;
  }

  /**
   * archived_answers
   *
   * It renders a Trainees attempts at the Questions for a give Test and
   * whether or not they were right. The view is filterable for developers but
   * only on the frontend (because this function is used on the backend too).
   *
   * @param mixed $test_id
   * @param mixed $resit_number
   *
   * @access public
   *
   * @return string Rendered template
   */
  public function archived_answers($test_id, $resit_number = null) {
    $archive = $this->get_augmented_archive($test_id, $resit_number);

    $data = array(
      'user'    => $this,
      'archive' => $archive
    );

    $view = tu()->get_path("/view/archived_answers");
    $view = tu()->in_frontend() ? apply_filters('tu_archived_answers', $view) : $view;

    return new View($view, $data);
  }

  /**
   * started_test
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return integer Time at which this user started the given test
   */
  public function started_test($test_id) {
    $started = get_user_meta($this->ID, "tu_started_test_{$test_id}", true);

    return $started;
  }

  /**
   * finished_test
   *
   * Return
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return integer Time at which this user started the given test
   */
  public function finished_test($test_id) {
    return get_user_meta($this->ID, "tu_finished_test_{$test_id}", true);
  }

  /**
   * taking_test
   *
   * Returns whether it looks like the user is currently taking a test, i.e.
   * they have pressed start, but not finish.
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return boolean
   */
  public function taking_test($test_id) {
    return $this->started_test($test_id) && !$this->finished_test($test_id);
  }

  /**
   * get_answer_to_question
   *
   * Returns this users answer to a particular question. Only available when
   * they are taking the test. (after the test, their answers are archived).
   *
   * @param integer $question_id
   *
   * @access public
   *
   * @return boolean
   */
  public function get_answer_to_question($question_id) {
    return get_user_meta($this->ID, "tu_answer_{$question_id}", true);
  }

  /**
   * has_answered_question
   *
   * @param object $question
   *
   * @access public
   *
   * @return Whether or not it looks like the user has answered the question,
   * or uploaded some files as their answer. (We only consider the latest
   * attempt at the question here...)
   */
  public function has_answered_question($question) {
    $dir      = wp_upload_dir();
    $attempt  = $this->get_resit_attempts($question->test->ID);
    $answered = $this->get_answer_to_question($question->ID) !== '';
    $target   = get_post_meta($question->ID, 'tu_file_attachment_amount', true);
    $uploaded = count($question->get_uploads($this, $attempt));
    $uploaded = $question->type === 'file_attachment' && $uploaded == $target;
    $answered = $answered || $uploaded;

    return $answered;
  }

  /**
   * save_temporary_answer_to_question
   *
   * - Save this users answer to a particular question. Remember, this is
   *   temporary because after a user finishes the test, this answer and all the
   *   others for the test are archived.
   * - Allow what is saved to the database to be filtered by developers, so
   *   they can create custom questions.
   * - If the Tin Can API is switched on, also create a statement
   *
   * @param object $question
   * @param string $answer
   *
   * @access public
   */
  public function save_temporary_answer_to_question($question, $answer) {
    $type = $question->get_type();

    $answer = apply_filters("tu_save_answer", $answer, $question);
    $answer = apply_filters("tu_save_answer_{$type}", $answer, $question);

    update_user_meta($this->ID, "tu_answer_{$question->ID}", $answer);

    tu()->tin_can->answer_question($this, $question, $answer);
  }

  /**
   * get_temporary_answers
   *
   * - Loads all this user's attempted answers for the given test ID
   * - Remember, these are only temporary answers - when the user submits their
   *   attempted answers these will be deleted, and moved to the archive.
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return array
   */
  public function get_temporary_answers($test_id) {
    $test    = Tests::factory($test_id);
    $answers = array();

    foreach ($test->questions as $question) {
      $attempt = $this->get_answer_to_question($question->ID);

      if ($attempt) {
        $answers[$question->ID] = $attempt;
      }
    }

    return $answers;
  }

  /**
   * clear_temporary_answers
   *
   * Fired when a user has finished a test. Delete answers from the user's meta
   * table, because they will have been archived now.
   *
   * @param integer $test_id
   *
   * @access private
   */
  private function clear_temporary_answers($test_id) {
    global $wpdb;

    $sql = "
      DELETE FROM {$wpdb->usermeta}
      WHERE  user_id = %d
      AND    meta_key IN (
        SELECT CONCAT('tu_answer_', ID)
        FROM   {$wpdb->posts}
        WHERE  post_type = %s
      )
    ";

    $wpdb->query($wpdb->prepare($sql, $this->ID, "tu_question_{$test_id}"));
  }

  /**
   * get_resit_attempts
   *
   * Return how many resit attempts a user has taken for a given test.
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return integer
   */
  public function get_resit_attempts($test_id) {
    return (int)get_user_meta($this->ID, "tu_resit_attempts_{$test_id}", true);
  }

  /**
   * resat_test
   *
   * Fired when a user has resat a test, increase the counter so we know they
   * have done a resit.
   *
   * @param integer $test_id
   *
   * @access public
   */
  public function resat_test($test_id) {
    $attempts = $this->get_resit_attempts($test_id) + 1;

    update_user_meta($this->ID, "tu_resit_attempts_{$test_id}", $attempts);
  }

  /**
   * set_group_ids
   *
   * Accept a bunch of group IDs, remove the user from all their Groups, and
   * then, add them to the group IDs just passed in. Effectively re-assigning
   * the user to some groups.
   *
   * @param array $group_ids
   *
   * @access public
   */
  public function set_group_ids($group_ids = array()) {
    delete_user_meta($this->ID, 'tu_group');

    foreach ($group_ids as $group_id) {
      $this->add_to_group($group_id);
    }
  }

  /**
   * get_group_ids
   *
   * Returns an array of IDs for Groups that this user belongs to (in the case
   * of a Trainee). Or, 'manages' in the case of a Group manager.
   *
   * @access public
   *
   * @return array
   */
  public function get_group_ids() {
    return get_user_meta($this->ID, 'tu_group');
  }

  /**
   * has_group
   *
   * Return whether or not this user belong to / manages a particular group.
   *
   * @param integer $group_id
   *
   * @access public
   *
   * @return boolean
   */
  public function has_group($group_id) {
    $group_ids = $this->get_group_ids();

    return (is_array($group_ids)) ? in_array($group_id, $group_ids) : false;
  }

  /**
   * add_to_group
   *
   * - Add this user to a Group if they've not already been added.
   * - Fire an action so developers can listen to when users are added to a
   *   group
   *
   * @param integer $group_id
   *
   * @access public
   */
  public function add_to_group($group_id) {
    if (!$this->has_group($group_id)) {
      add_user_meta($this->ID, 'tu_group', $group_id);
      do_action('tu_user_added_to_group', $this, $group_id);
    }
  }

  /**
   * remove_from_group
   *
   * - Remove this user from a Group.
   * - Fire an action so developers can listen to when users are removed from
   *   a group
   *
   * @param integer $group_id
   *
   * @access public
   */
  public function remove_from_group($group_id) {
    delete_user_meta($this->ID, 'tu_group', $group_id);
    do_action('tu_user_removed_from_group', $this, $group_id);
  }

  /**
   * get_groups
   *
   * @access public
   *
   * @return array The Group posts that this user belongs to / manages.
   */
  public function get_groups() {
    $group_ids = $this->get_group_ids();

    if ($group_ids) {
      return Groups::find_all(array('include' => $group_ids));
    } else {
      return array();
    }
  }

  /**
   * get_group_list
   *
   * Return a string which lists the Groups for this user, sort them and
   * truncate them. Suitable for a very vague overview as to which groups
   * they are in.
   *
   * @param integer $limit
   *
   * @access public
   *
   * @return string
   */
  public function get_group_list($limit = 0) {
    $groups = $this->get_groups();
    $titles = array();

    foreach ($groups as $group) {
      $titles[] = $group->post_title;
    }

    sort($titles);

    $list = join(', ', $titles);

    if ($limit > 0 && strlen($list) >= $limit) {
      $list = rtrim(substr($list, 0, $limit), ',').'...';
    }

    return $list;
  }

  /**
   * get_role
   *
   * @access public
   *
   * @return string The main role associated with this user.
   */
  public function get_role() {
    return $this->roles[0];
  }

  /**
   * is_administrator
   *
   * @access public
   *
   * @return boolean Whether or not this user's role is that of an Administrator
   */
  public function is_administrator() {
    return (
      user_can($this->user, 'administrator') ||
      is_super_admin($this->user->ID)
    );
  }

  /**
   * is_trainee
   *
   * @access public
   *
   * @return boolean Whether or not this user's main role is that of a Trainee.
   */
  public function is_trainee() {
    return (
      user_can($this->user, 'tu_trainee') &&
      !is_super_admin($this->user->ID)
    );
  }

  /**
   * is_group_manager
   *
   * @access public
   *
   * @return boolean whether or not this user's main role is that of a Group
   * manager.
   */
  public function is_group_manager() {
    return (
      user_can($this->user, 'tu_group_manager') &&
      !is_super_admin($this->user->ID)
    );
  }

  /**
   * get_access_level_ids
   *
   * - Returns the IDs of Levels which this user can access.
   * - These will include all un-grouped levels (i.e. they're not restricted to
   *   a Trainees/Group managers with a particular Group)
   *
   * @access public
   *
   * @return array
   */
  public function get_access_level_ids() {
    global $wpdb;

    $cache_grp = 'tu_user_access_level_ids';
    $cache_id  = $this->ID;
    $level_ids = wp_cache_get($cache_id, $cache_grp, false, $found);

    if ($found) return $level_ids;

    $level_ids = Levels::get_derestricted_ids();
    $group_ids = $this->get_group_ids();

    if ($group_ids) {
      $sql = "
        SELECT DISTINCT p.ID
        FROM   {$wpdb->posts} p
        JOIN   {$wpdb->postmeta} m
        ON     m.post_id   = p.ID
        WHERE  p.post_type = 'tu_level'
        AND    m.meta_key  = 'tu_group'
        AND    m.meta_value  IN (".join(',', $group_ids).")
        AND    p.post_status = 'publish'
      ";

      foreach ($wpdb->get_results($sql) as $row) {
        $level_ids[] = $row->ID;
      }
    }

    wp_cache_set($cache_id, $level_ids, $cache_grp);

    return $level_ids;
  }

  /**
   * get_access_test_ids
   *
   * - Returns IDs of Tests that this user can access.
   * - These will include Tests whose Level is not in a group, or is in a group
   *   but one which this user is allowed to access.
   * - Basically it all hinges of a user's ability to access a Level.
   *
   * @access public
   *
   * @return array
   */
  public function get_access_test_ids() {
    global $wpdb;

    $cache_grp = 'tu_user_access_test_ids';
    $cache_id  = $this->ID;
    $test_ids  = wp_cache_get($cache_id, $cache_grp, false, $found) ?: array();

    if ($found) return $test_ids;

    if (count($this->access_level_ids) >= 1) {
      $sql = "
        SELECT DISTINCT ID
        FROM   {$wpdb->posts} p
        JOIN   {$wpdb->postmeta} m
        ON     p.ID = m.post_id
        WHERE  p.post_type = 'tu_test'
        AND    m.meta_key  = 'tu_level_id'
        AND    m.meta_value IN (".join(',', $this->access_level_ids).")
      ";

      foreach ($wpdb->get_results($sql) as $row) {
        $test_ids[] = $row->ID;
      }
    }

    wp_cache_set($cache_id, $test_ids, $cache_grp);

    return $test_ids;
  }

  /**
   * get_results
   *
   * - Get Result posts for this user.
   * - Join on the related Tests to get their menu order, so that when listed,
   *   Result posts reflect the general order that the tests should be taken in.
   *
   * - Important Note: This was originally used to show the list of Trainee Test
   *   Results on their My Results page. However, now we use a walker to list
   *   Levels instead. This query may still prove useful in future though.
   *
   * @access public
   *
   * @return array
   */
  public function get_results() {
    global $wpdb;

    return get_as('Results', $wpdb->get_results($wpdb->prepare("
      SELECT   r.*
      FROM     {$wpdb->posts} r
      JOIN     {$wpdb->postmeta} rm
      ON       rm.post_id = r.ID
      JOIN     {$wpdb->postmeta} rm2
      ON       rm2.post_id = r.ID
      JOIN     {$wpdb->posts} t
      ON       t.ID = rm2.meta_value
      WHERE    rm.meta_key  = 'tu_user_id'
      AND      rm2.meta_key = 'tu_test_id'
      AND      r.post_type REGEXP('tu_result_[0-9]+')
      AND      rm.meta_value = %d
      AND      r.post_status = 'publish'
      GROUP BY r.post_type
      ORDER BY t.menu_order, r.post_date
    ", $this->ID)));
  }

  /**
   * get_result
   *
   * Returns a users result-post for a particular test. Note that this is not
   * the definitive place to get a users actual result, this is just a
   * temporary result post that lets the user access their results. Their
   * actual results are store in the archive!
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return object|null
   */
  public function get_result($test_id) {
    $results = get_posts_as('Results', array(
      'post_type'    => "tu_result_{$test_id}",
      'numberposts'  => 1,
      'meta_key'     => 'tu_user_id',
      'meta_value'   => $this->ID,
      'post_status'  => 'publish, draft',
      'nesting'      => false
    ));

    return (count($results) > 0) ? $results[0] : null;
  }

  /**
   * get_in_progress_test_ids
   *
   * @access public
   *
   * @return array Of Test IDs that this user has started, but not finished
   */
  public function get_in_progress_test_ids() {
    global $wpdb;

    $sql = "
      SELECT SUBSTRING(meta_key, 17) AS test_id
      FROM {$wpdb->usermeta}
      WHERE meta_key REGEXP('tu_started_test_[0-9+]')
    ";

    $test_ids = array();

    foreach ($wpdb->get_results($sql) as $row) {
      $test_ids[] = $row->test_id;
    }

    return $test_ids;
  }

  /**
   * has_visited_resource
   *
   * Returns whether or not this user has visited a particular resource.
   * This just helps them remember where they got up to studying even if they
   * deleted their browser history or are using a different computer.
   *
   * @param integer $resource_id
   *
   * @access public
   *
   * @return boolean
   */
  public function has_visited_resource($resource_id) {
    return in_array($resource_id, (array)$this->get_visited_resource_ids());
  }

  /**
   * get_visited_resource_ids
   *
   * @access public
   *
   * @return array IDs of resources that this user has visited.
   */
  public function get_visited_resource_ids() {
    return get_user_meta($this->ID, 'tu_visited_resource_ids', true);
  }

  /**
   * save_visitied_resource
   *
   * Load the users visited resources, and push on the new resource.
   * Then save the visited resource IDs.
   *
   * @param integer $resource_id
   *
   * @access public
   */
  public function save_visitied_resource($resource_id) {
    if ($this->has_visited_resource($resource_id)) return;

    $resource_ids = (array)$this->get_visited_resource_ids();

    $resource_ids[] = $resource_id;

    update_user_meta($this->ID, 'tu_visited_resource_ids', $resource_ids);
  }

  /**
   * resource_schedule_ok
   *
   * Return whether or not this user can access the resource based on the
   * scheduling information
   *
   * @return boolean
   */
  public function resource_schedule_ok($resource) {
    list($ok) = $resource->is_available_to_user($this);
    return $ok;
  }

  /**
   * get_performance_data
   *
   * Go through this user's archive (all their test attempts, even resits)
   * and build an array structure suitable to pass to Google Visualisations,
   * to create a line graph of their percentages over time.
   *
   * @access public
   *
   * @return array
   */
  public function get_performance_data() {
    $data = array(array(
      tu()->config['tests']['single'],
      __('Percentage', 'trainup')
    ));

    foreach ($this->archives as $archive) {
      $data[] = array($archive['test_title'], (int)$archive['percentage']);
    }

    return $data;
  }

  /**
   * get_rank
   *
   * - Loads the archive for this test by highest percentage first
   * - Filter down the array to just this specific user
   *   (Note array_values is used to re-index the array so we can pull out
   *   the first item an assume it is this specific user).
   * - Note: A Group Manager can't see their rank because Test Archives are
   *   filtered to Trainees that the Group Manager can access. And the Group
   *   Manager themselves are not a Trainee and so we won't have their result
   *   in order to determine the rank.
   *
   * @param integer $test_id
   *
   * @access public
   *
   * @return integer This user's rank in the system, optionally for a specific
   * test.
   */
  public function get_rank($test_id = null) {
    $archives = Tests::factory($test_id)->get_archives(array(
      'order_by' => 'percentage',
      'order'    => 'DESC'
    ));

    $user_id = $this->ID;

    $filter = function($row) use ($user_id) {
      return $row['user_id'] == $user_id;
    };

    $archives = array_values(array_filter($archives, $filter));

    return $archives ? $archives[0]['rank'] : __('N/A', 'trainup');
  }

  /**
   * get_pass_percentage
   *
   * Return a percentage value of how much progress this user has made at
   * completing the levels. (That is, _passing_ the levels' test's.)
   *
   * @param object optional level
   *
   * @return integer
   */
  public function get_pass_percentage($level = null) {
    return Levels::get_pass_percentage($this, $level);
  }

  /**
   * passed_test
   *
   * Accepts an ID of a Test, get's the user's latest archived attempt at
   * that test, and returns whether or not they passed it.
   *
   * @param integer $test_id
   *
   * @return boolean
   *
   */
  public function passed_test($test_id) {
    $archive = $this->get_archive($test_id);
    return $archive['passed'];
  }

  /**
   * is_eligible_for
   *
   * - Load the eligibility config for the level provided
   * - Find this user's *latest attempt* at the Level's Test from the archive
   * - The user is considered eligible if they have passed all the required
   *   tests for the level.
   *
   * @param mixed $level
   *
   * @access public
   *
   * @return array Whether or not this user is eligible for accessing the level
   * and its relations.
   */
  public function is_eligible_for_level($level) {
    $config    = $level->get_eligibility_config();
    $test_ids  = $config['test_ids'];
    $error     = '';
    $passed    = 0;
    $failed_id = 0;

    foreach ($test_ids as $test_id) {
      if ($this->passed_test($test_id)) {
        $passed++;
      } else {
        $failed_id = $test_id;
      }
    }

    $eligible = (
      $this->is_administrator() ||
      count($test_ids) === $passed
    );

    if (!$eligible) {
      $test = Tests::factory($failed_id ?: $test_ids[0]);
      $error = sprintf(
        __('You must have passed "%1$s" before you can access "%2$s"', 'trainup'),
        $test->post_title, $level->post_title
      );
    }

    $result = array($eligible, $error);
    $result = apply_filters('tu_user_is_eligible_for_level', $result, $this, $level);

    return $result;
  }

  /**
   * can_access_level
   *
   * - Returns whether or not this user can access a particular Level.
   * - If they are an administrator, then yes of course they can
   * - Otherwise, they must be a Trainee or a Group Manager and be in a Group
   *   which this Level is assigned to.
   *
   * @param object $level
   *
   * @access public
   *
   * @return array
   */
  public function can_access_level($level) {
    $error    = '';
    $_group   = strtolower(tu()->config['groups']['single']);
    $role_ok  = current_user_can('tu_frontend');
    $group_ok = (
      $this->is_administrator() ||
      in_array($level->ID, $this->get_access_level_ids())
    );

    list($eligibility_ok, $eligibility_error) = $this->is_eligible_for_level($level);

    if (!$role_ok) {
      $error = sprintf(__('Access denied', 'trainup'));
    } else if (!$group_ok) {
      $error = sprintf(__('You are not in the correct %1$s', 'trainup'), $_group);
    } else if (!$eligibility_ok) {
      $error = $eligibility_error;
    }

    $result = array(!$error, $error);
    $result = apply_filters('tu_user_can_access_level', $result, $this, $level);

    return $result;
  }

  /**
   * can_access_test
   *
   * - This user can access the test if it can access the Test's Level, and
   *   they've not run out of time taking the test.
   * - Note: We return an error code so that we can distinguish certain events
   *   so that we can automatically finish the test on behalf of the user if
   *   they do run out of time.
   *
   * @param object $test
   *
   * @access public
   *
   * @return array Whether or not this user can access a particular Test.
   */
  public function can_access_test($test) {
    $code = null;

    list($ok, $error) = $this->can_access_level($test->level);

    if (!$this->has_time_left($test)) {
      $code  = 0x01;
      $error = sprintf(__('Sorry, you have run out of time', 'trainup'));
    }

    $result = array(!$error, $error, $code);
    $result = apply_filters('tu_user_can_access_test', $result, $this, $test);

    return $result;
  }

  /**
   * can_access_resource
   *
   * - Returns whether or not this user can access a particular Resource.
   * - If resources are to be locked-down during the taking of tests, then
   *   prevent access.
   * - If the resource is scheduled, then check it is currently available
   *
   * @param object $resource
   *
   * @access public
   *
   * @return array
   */
  public function can_access_resource($resource) {
    $level      = $resource->level;
    $test       = $level->test;
    $has_test   = (bool)$test;
    $lock_down  = (bool)tu()->config['resources']['lock_during_test'];
    $_resources = strtolower(tu()->config['resources']['plural']);
    $_test      = strtolower(tu()->config['tests']['single']);

    list($ok, $error) = $this->can_access_level($level);

    if ($lock_down && $has_test && $this->taking_test($test->ID)) {
      $error = sprintf(
        __('Access to %1$s is denied whilst you are taking a %2$s', 'trainup'),
        $_resources, $_test
      );
    } else if ($resource->is_scheduled()) {
      list(, $error) = $resource->is_available_to_user($this);
    }

    $result = array(!$error, $error);
    $result = apply_filters('tu_user_can_access_resource', $result, $this, $resource);

    return $result;
  }

  /**
   * can_access_question
   *
   * @param object $question
   *
   * @access public
   *
   * @return array
   */
  public function can_access_question($question) {
    $test  = $question->test;
    $_test = strtolower(tu()->config['tests']['single']);

    list($ok, $error) = $this->can_access_test($test);

    if (!tu()->user->is_administrator()) {
      if (!$this->started_test($test->ID)) {
        $error = sprintf(__('You have not started the %1$s yet', 'trainup'), $_test);
      } else if ($this->finished_test($test->ID)) {
        $error = sprintf(__('You have already completed the %1$s', 'trainup'), $_test);
      }
    }

    $result = array(!$error, $error);
    $result = apply_filters('tu_user_can_access_question', $result, $this, $question);

    return $result;
  }

  /**
   * can_access_result
   *
   * - Return whether or not this user can access a particular Result.
   * - If they are an administrator, then yes of course they can
   * - Otherwise, they must be the Trainee that the Result is actually for,
   *   or a Group manager, who has access to the Trainee whose Result it is
   *   (i.e. that Trainee is in one of their Groups).
   *
   * @param object $result
   *
   * @access public
   *
   * @return array
   */
  public function can_access_result($result) {
    $error = '';

    $ok = (
      $this->ID == $result->user->ID ||
      $this->is_administrator()      ||
      (
        $this->is_group_manager() &&
        in_array($result->ID, $this->get_access_result_ids())
      )
    );

    if (!$ok) {
      $error = __('Access denied', 'trainup');
    }

    $result = array($ok, $error);
    $result = apply_filters('tu_user_can_access_result', $result, $this, $result);

    return $result;
  }

  /**
   * can_resit_test
   *
   * - Return whether or not this user can resit a particular Test.
   * - They must have access to the Test (via its Level's Groups)
   *   and they must have enough resit attempts left.
   * - 0 = no resit attempts, and -1 = Unlimited resit attempts
   *
   * @param object $test
   *
   * @access public
   *
   * @return array
   */
  public function can_resit_test($test) {
    list($ok, $error) = $this->can_access_test($test);

    $taken   = $this->get_resit_attempts($test->ID);
    $allowed = $test->get_resit_attempts();

    if ($allowed >= 0 && $taken >= $allowed) {
      $error = __('You have exceeded the amount of resit attempts', 'trainup');
    }

    $result = array(!$error, $error);
    $result = apply_filters('tu_user_can_resit_test', $result, $this, $test);

    return $result;
  }

  /**
   * can_access_trainee
   *
   * - Returns whether or not the User can access a Trainee
   * - The default is false, because only Administrators can access them, and
   *   Group managers can access some of them depending on their group(s).
   *
   * @param object $trainee
   *
   * @access public
   *
   * @return array
   */
  public function can_access_trainee($trainee) {
    $result = array(false, '');
    $result = apply_filters('tu_user_can_access_trainee', $result, $this, $trainee);

    return $result;
  }

}
