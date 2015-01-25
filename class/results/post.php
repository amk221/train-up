<?php

/**
 * A class to represent a single Result WP_Post
 *
 * @package Train-Up!
 * @subpackage Results
 */

namespace TU;

class Result extends Post {

  /**
   * $template_file
   *
   * The template file that is used to render Results.
   *
   * @var string
   *
   * @access protected
   */
  protected $template_file = 'tu_result';

  /**
   * __construct
   *
   * - When a Result is instantiated construct the post as normal, then if it
   *   is actually active, check that the current logged in user can access it.
   * - Add some filters to force the title of the page.
   *
   * @param mixed $post
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($post, $active = false) {
    parent::__construct($post, $active);

    if ($this->is_active()) {
      list($ok, $error) = tu()->user->can_access_result($this);

      add_filter('the_title', array($this, '_override_post_title'), 10, 2);
      add_filter('single_post_title', array($this, '_override_title'));
      add_action('wp_insert_comment', array($this, '_wp_insert_comment'), 10, 2);

      if ($ok) {
        $this->handle_downloading_of_files();
      } else {
        $this->bail($error);
      }
    }
  }

  /**
   * as_tin_can_object
   *
   * - Convert this result to a format that the Tin Can API will understand.
   * - Remember that a Train-Up! Result always refers to a Trainee's most recent
   *   attempt at a test.
   *
   * @access public
   *
   * @return object
   */
  public function as_tin_can_object() {
    $archive          = $this->get_archive();
    $result           = new Tin_can_result;
    $score            = new Tin_can_score;
    $score->raw       = (int)$archive['percentage'];
    $score->scaled    = $score->raw / 100;
    $score->min       = 0;
    $score->max       = 100;
    $result->score    = $score;
    $result->success  = (bool)$archive['passed'];
    $result->duration = $this->get_duration();

    return $result;
  }

  /**
   * _override_title
   *
   * - Fired on `single_post_title`
   * - For example: Changes the <title> tag for the active result
   *
   * @param string $title
   *
   * @access private
   *
   * @return string The altered title
   */
  public function _override_title($title) {
    return sprintf(__('%1$s results', 'trainup'), $this->test->post_title);
  }

  /**
   * _override_post_title
   *
   * - Fired on `the_title`
   * - If the post title being rendered is for *this* post, then override it
   * - For example: Changes the <h1> for the active result
   *
   * @see _override_title
   *
   * @param string $title
   * @param integer $post_id
   *
   * @access private
   *
   * @return string The altered title
   */
  public function _override_post_title($title, $post_id) {
    if ($post_id == $this->ID) {
      return $this->_override_title($title);
    }
    return $title;
  }

  /**
   * set_test_id
   *
   * Set the ID of the Test post that this Result should be for.
   *
   * @param integer $test_id
   *
   * @access public
   */
  public function set_test_id($test_id) {
    add_post_meta($this->ID, 'tu_test_id', $test_id, true);
  }

  /**
   * set_user_id
   *
   * Set the ID of the User that this Result is for.
   *
   * @param integer $user_id
   *
   * @access public
   */
  public function set_user_id($user_id) {
    add_post_meta($this->ID, 'tu_user_id', $user_id, true);
  }

  /**
   * get_test_id
   *
   * @access public
   *
   * @return integer The ID of the Test post that this Result is for
   */
  public function get_test_id() {
    return get_post_meta($this->ID, 'tu_test_id', true);
  }

  /**
   * get_test
   *
   * @access public
   *
   * @return object Test post associated with this result
   */
  public function get_test() {
    return Tests::factory($this->test_id);
  }

  /**
   * get_user_id
   *
   * @access public
   *
   * @return integer The ID of the user that this Result is for
   */
  public function get_user_id() {
    return get_post_meta($this->ID, 'tu_user_id', true);
  }

  /**
   * get_duration
   *
   * Get the duration in 8601 date duration format. How long it took from the
   * time the Trainee started the test to the time they submitted their answers.
   *
   * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
   *
   * @access public
   *
   * @return string
   */
  public function get_duration() {
    $archive  = $this->get_archive();
    $string   = $archive['duration'] . ' seconds';
    $interval = date_interval_create_from_date_string($string);
    $duration = interval_to_str($interval);

    return $duration;
  }

  /**
   * get_user
   *
   * Return a user object that is the correct instance for that role of user.
   * (This is because Administrators and Group mangagers, as well as Trainees
   * can have Test Results).
   *
   * @access public
   *
   * @return object User associated with this Result
   */
  public function get_user() {
    $user = get_user_by('id', $this->user_id);

    list($instance) = get_user_instance($user);

    return $instance;
  }

  /**
   * get_featured_image
   *
   * A Result's featured image is taken from the Level that the Test is in that
   * this Result is for.
   *
   * @access public
   *
   * @return string The URL of the image
   */
  public function get_featured_image() {
    return $this->test->level->featured_image;
  }

  /**
   * get_archive
   *
   * A Test Result's archive is always a Trainee's latest attempt at the Test.
   *
   * @access public
   *
   * @return object
   */
  public function get_archive() {
    return $this->user->get_archive($this->get_test_id());
  }

  /**
   * get_augmented_archive
   *
   * A Test Result's archive but with extra meta and shiz
   *
   * @access public
   *
   * @return object
   */
  public function get_augmented_archive() {
    return $this->user->get_augmented_archive($this->get_test_id());
  }

  /**
   * set_manual_percentage
   *
   * - Use this Result as a means to getting the associated archive data
   *   and update it with the specified manual percentage.
   *   Note, this only allows you to change a Trainee's most recent attempt
   *   at the Test that this Result is for.
   * - We then have to re-calculate the grade to reflect the new percentage.
   *
   * @param mixed $percentage
   *
   * @access public
   *
   * @return mixed Value.
   */
  public function set_manual_percentage($percentage) {
    global $wpdb;

    list($grade, $passed) = $this->test->get_grade_for_percentage($percentage);

    $sql = "
      UPDATE {$wpdb->prefix}tu_archive
      SET
        percentage  = %d,
        grade       = %s,
        passed      = %d
      WHERE user_id = %d
      AND   test_id = %d
      ORDER BY resit_number DESC
      LIMIT 1
    ";

    $wpdb->query($wpdb->prepare(
      $sql,
      $percentage,
      $grade,
      $passed,
      $this->get_user_id(),
      $this->get_test_id()
    ));
  }

  /**
   * get_breadcrumb_trail
   *
   * Returns the array of breadcrumbs for this Result. Let them be filtered
   * so that developers can customise them.
   *
   * @access public
   *
   * @return array
   */
  public function get_breadcrumb_trail() {
    $crumbs             = parent::get_breadcrumb_trail();
    $my_results         = Pages::factory('My_results');
    $crumbs[1]['title'] = $this->test->post_title;

    $crumbs = array(
      $crumbs[0],
      array(
        'url'   => $my_results->url,
        'title' => $my_results->post_title
      ),
      $crumbs[1]
    );

    $result = apply_filters('tu_result_crumbs', $crumbs);

    return $result;
  }

  /**
   * _wp_insert_comment
   *
   * - Fired on `_wp_insert_comment` when this Result post is active.
   * - Send emails to the Group managers alerting them that somebody has
   *   commented on their Test Result. (Either another Group Manager or
   *   an Administrator).
   * - Send an email to the Trainee when a Group manager or Administrator
   *   comments on their Test Result.
   * - Exclude sending an email to the commentor, pointless.
   *
   * @access private
   */
  public function _wp_insert_comment($comment_id, $comment) {
    $emails  = array();
    $who     = tu()->config['tests']['comment_result_notifications'];
    $_test   = strtolower(tu()->config['tests']['single']);
    $url     = login_url($this->url);
    $subject = sprintf(__('New %1$s result comment', 'trainup'), $_test);
    $message = sprintf(
      __('New comment on %1$s %2$s result for %3$s', 'trainup'),
      $this->test->post_title, $_test, $this->user->display_name
    );
    $body = "{$message}\n\n{$comment->comment_content}\n\n{$url}";

    if (isset($who['administrators'])) {
      foreach (Administrators::mailing_list() as $address) {
        $emails[$address] = true;
      }
    }

    if (isset($who['group_managers']) && $this->user->is_trainee()) {
      foreach ($this->user->get_group_managers() as $group_manager) {
        $emails[$group_manager->user_email] = true;
      }
    }

    if (isset($who['trainee'])) {
      $emails[$this->user->user_email] = true;
    }

    unset($emails[$comment->comment_author_email]);

    foreach (array_keys($emails) as $to) {
      wp_mail($to, $subject, $body);
    }
  }

  /**
   * handle_downloading_of_files
   *
   * When requests are made to this Test Result, listen out for requests to
   * download any files that the Trainee might have uploaded as their answer to
   * one of the questions.
   *
   * Serve them as a download in an attempt to mask the physical location to
   * prevent easy access to other Trainees files. We only serve their latest
   * attempted uploaded file using this method because Test Results only ever
   * represent a Trainees latest attempt (past attempts are archived).
   *
   * @access private
   */
  private function handle_downloading_of_files() {
    $do_download = (
      isset($_REQUEST['tu_action']) &&
      $_REQUEST['tu_action'] == 'download_file' &&
      isset($_REQUEST['tu_question_id']) &&
      !empty($_REQUEST['tu_file'])
    );

    if (!$do_download) return;

    $name    = sanitize_file_name($_REQUEST['tu_file']);
    $uid     = $this->user->ID;
    $qid     = (int)$_REQUEST['tu_question_id'];
    $q       = Questions::factory($qid);
    $test_id = $q->test->ID;
    $num     = $this->user->get_resit_attempts($test_id);
    $dir     = wp_upload_dir();
    $slug    = tu()->get_slug();
    $path    = "{$dir['basedir']}/{$slug}/{$uid}/{$qid}/{$num}/{$name}";
    $type    = wp_check_filetype($path);
    $type    = $type['type'];

    if (file_exists($path))  {
      header("Content-Type: {$type}; charset=utf-8");
      header("Content-Disposition: filename={$name}");
      readfile($path);
      exit;
    } else {
      wp_die(sprintf(__('%1$s not found', 'trainup'), $name));
    }
  }

  /**
   * get_uploads
   *
   * - Go through all this user's uploads folder.
   * - Get Questions that are related to this Result's Test
   * - Only get the uploaded files for the latest attempt. (Remember Test
   *   Results only related to a user's most recent attempt.)
   * - This isn't used by Train-Up! but is available because it will probably
   *   be useful to developers.
   * - Structure:
   * {
   *   100: ['http://...foo.doc']
   *   101: [...]
   * }
   *
   * @access public
   *
   * @return array of files uploaded to this Result's Test's Questions!
   */
  public function get_uploads() {
    $dir   = wp_upload_dir();
    $uid   = $this->user->ID;
    $num   = $this->user->get_resit_attempts($this->test->ID);
    $slug  = tu()->get_slug();
    $path  = "{$dir['basedir']}/{$slug}/{$uid}";
    $files = array();

    $question_paths = (array)glob("{$path}/*", GLOB_ONLYDIR);

    foreach ($question_paths as $question_path) {

      $qid = basename($question_path);

      if (!in_array($qid, $this->test->question_ids)) continue;

      $files[$qid] = array();

      $file_paths = (array)glob("{$question_path}/{$num}/*");

      foreach ($file_paths as $file_path) {
        $file = basename($file_path);

        if (tu()->in_frontend()) {
          $url = add_query_arg(array(
            'tu_action' => 'download_file',
            'tu_file'   => urlencode($file)
          ), $this->url);
        } else {
          $url = "{$dir['baseurl']}/{$slug}/{$uid}/{$qid}/{$num}/{$file}";
        }

        $files[$qid][] = $url;
      }
    }

    return $files;
  }

}
