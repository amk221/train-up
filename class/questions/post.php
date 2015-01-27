<?php

/**
 * A class to represent a single Question WP_Post
 *
 * @package Train-Up!
 * @subpackage Questions
 */

namespace TU;

class Question extends Post {

  /**
   * $template_file
   *
   * The template file that is used to render Questions.
   *
   * @var string
   *
   * @access protected
   */
  protected $template_file = 'tu_question';

  /**
   * __construct
   *
   * When a Question is instantiated construct the post as normal, then if it is
   * actually active, check the permissions and listen out for actions against
   * it to handle saving of a user's answers.
   *
   * @param object $post
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($post, $active = false) {
    parent::__construct($post, $active);

    if ($this->is_active()) {
      list($ok, $error) = tu()->user->can_access_question($this);

      if ($ok) {
        $this->handle_saving_of_answers();
        $this->handle_downloading_of_files();
        add_action('the_content', array($this, '_the_content'));
        add_action('wp_enqueue_scripts', array($this, '_add_assets'));
      } else {
        $this->bail($error);
      }
    }
  }

  /**
   * as_tin_can_activity
   *
   * Convert this question to a format that the Tin Can API will understand.
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
      'type'            => "http://activitystrea.ms/schema/{$version}/question",
      'name'            => array( get_locale() => $this->post_title ),
      'description'     => array( get_locale() => $this->get_title(true, 100) ),
      'interactionType' => $this->tin_can_interaction_type()
    );

    return $activity;
  }

  /**
   * handle_saving_of_answers
   *
   * When requests are made to this Question, listen out for a user's answer
   * to it. If you change this function, also change Questions::ajax_save_answer
   * which is the ajax version.
   *
   * @see tu_saved_answer_ajax
   * @access private
   */
  private function handle_saving_of_answers() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $answer = isset($_POST['tu_answer']) ? $_POST['tu_answer'] : '';

    tu()->user->save_temporary_answer_to_question($this, $answer);

    $response = array(
      'type' => 'success',
      'msg'  => apply_filters(
        'tu_save_answer_message',
        __('Your answer was saved', 'trainup')
      )
    );

    $response = apply_filters('tu_saved_answer', $response, $this, $answer);

    tu()->message->set_flash($response['type'], $response['msg']);
  }

  /**
   * handle_downloading_of_files
   *
   * When requests are made to this Question, listen out for requests to
   * download any files that the Trainee might have uploaded as their answer.
   * Serve them as a download in an attempt to mask the physical location to
   * prevent easy access to other Trainees files. We only serve their latest
   * attempted uploaded file using this method (because they'd be viewing the
   * actual question).
   *
   * @access private
   */
  private function handle_downloading_of_files() {
    $do_download = (
      isset($_REQUEST['tu_action']) &&
      $_REQUEST['tu_action'] == 'download_file' &&
      !empty($_REQUEST['tu_file'])
    );

    if (!$do_download) return;

    $name = sanitize_file_name($_REQUEST['tu_file']);
    $uid  = tu()->user->ID;
    $qid  = $this->ID;
    $num  = tu()->user->get_resit_attempts($this->test->ID);
    $dir  = wp_upload_dir();
    $slug = tu()->get_slug();
    $path = "{$dir['basedir']}/{$slug}/{$uid}/{$qid}/{$num}/{$name}";
    $name = basename($path);
    $type = wp_check_filetype($path);
    $type = $type['type'];

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
   * get_test_id
   *
   * @access public
   *
   * @return integer ID of the Test that this Question belongs to.
   */
  public function get_test_id() {
    return get_post_meta($this->ID, 'tu_test_id', true);
  }

  /**
   * set_test_id
   *
   * Set the ID of the Test that this Question should belong to.
   *
   * @param integer $test_id
   *
   * @access public
   */
  public function set_test_id($test_id) {
    add_post_meta($this->ID, 'tu_test_id', $test_id, true);
  }

  /**
   * get_test
   *
   * @access public
   *
   * @return object Test post that this Question belongs to
   */
  public function get_test() {
    return Tests::factory($this->test_id);
  }

  /**
   * get_answers
   *
   * Returns an array of potential answers to this Question.
   * (Only relevant for multiple choice questions).
   *
   * @access public
   *
   * @return array
   */
  public function get_answers() {
    return (array)get_post_meta($this->ID, 'tu_answers', true);
  }

  /**
   * get_comparison
   *
   * Returns the comparison slug used to check whether any answers to this
   * Question are correct or not. (Only relevant for single-answer questions)
   *
   * @access public
   *
   * @return string
   */
  public function get_comparison() {
    return get_post_meta($this->ID, 'tu_comparison', true) ?: 'equal-to';
  }

  /**
   * set_pattern_modifier
   *
   * Saves the regular expression modifier to use if this question is a pattern
   * matching question. e.g. If the pattern was /Foo,\sBar/i then the modifier
   * is 'i'.
   *
   * @param mixed $modifier
   *
   * @access public
   */
  public function set_pattern_modifier($modifier) {
    update_post_meta($this->ID, 'tu_pattern_modifier', $modifier);
  }

  /**
   * get_pattern_modifier
   *
   * @access public
   *
   * @return string The modifier, e.g. i, m, or g etc
   */
  public function get_pattern_modifier() {
    return get_post_meta($this->ID, 'tu_pattern_modifier', true) ?: '';
  }

  /**
   * get_correct_answer
   *
   * Returns the the correct answer to this question, be it a multiple choice
   * or a single-answer, or a custom question.
   *
   * @access public
   *
   * @return string
   */
  public function get_correct_answer() {
    return get_post_meta($this->ID, 'tu_correct_answer', true);
  }

  /**
   * save_multiple_answers
   *
   * Accept a bunch of potential answers to this Question, and one that is
   * the actual correct answer. Save them.
   *
   * @param array $answers
   * @param string $correct
   *
   * @access public
   */
  public function save_multiple_answers($answers, $correct) {
    update_post_meta($this->ID, 'tu_answers', $answers);
    update_post_meta($this->ID, 'tu_correct_answer', $correct);
  }

  /**
   * save_single_answer
   *
   * Save the answer to this Question, also save how it is determined to be
   * correct. (Only relevant for single-answer questions)
   *
   * @param string $answer
   * @param string $comparison
   *
   * @access public
   */
  public function save_single_answer($answer, $comparison = 'equal-to') {
    update_post_meta($this->ID, 'tu_correct_answer', $answer);
    update_post_meta($this->ID, 'tu_comparison', $comparison);
  }


  /**
   * get_title
   *
   * Infer the Question's title from its post_content. unless a custom title
   * has been specified.
   *
   * @param mixed $strip_tags Optionally strip tags from the post_content
   * @param int   $limit      Optionally limit and append ellipsis
   *
   * @access public
   *
   * @return mixed Value.
   */
  public function get_title($strip_tags = true, $limit = 0) {
    $title = $this->has_custom_title() ? $this->post_title : $this->post_content;
    $title = strip_shortcodes($title);

    $title = apply_filters('tu_question_title', $title, $this);
    $title = apply_filters("tu_question_title_{$this->type}", $title, $this);

    if ($limit > 0 && strlen($title) >= $limit) {
      $title = rtrim(substr($title, 0, $limit)).'...';
    }

    if ($strip_tags) {
      $title = strip_tags($title);
    }

    return trim($title);
  }

  /**
   * tin_can_interaction_type
   *
   * - Return a string that defines the type of interaction a user would make
   *   when answering this Question.
   * - This is filterable so that developers can return an appropriate value
   *   for their custom questions.
   *
   * @access public
   *
   * @return string
   */
  public function tin_can_interaction_type() {
    if ($this->type === 'multiple') {
      $type = 'choice';
    } else if ($this->type === 'single') {
      $comparisons = Questions::get_comparisons();
      $type        = $comparisons[$this->comparison]['cmi'];
    } else {
      $type = 'other';
    }

    $result = apply_filters("tu_tin_can_interaction_type_{$this->type}", $type);

    return $result;
  }

  /**
   * get_type
   *
   * @access public
   *
   * @return string The type of Question, 'multiple', 'single', or [custom]
   */
  public function get_type() {
    return get_post_meta($this->ID, 'tu_question_type', true);
  }

  /**
   * set_type
   *
   * Set the type of Question, 'multiple', 'single', or [custom]
   *
   * @param string $type
   *
   * @access public
   */
  public function set_type($type) {
    update_post_meta($this->ID, 'tu_question_type', $type, $this->type);
  }

  /**
   * can_edit
   *
   * Returns whether or not this Question can be edited. A Question cannot be
   * edited if the Test that it belongs to has been started by some Trainees,
   * otherwise it would be unfair to start changing it.
   *
   * @see Test::can_edit if you are desperate
   * @access public
   *
   * @return boolean
   */
  public function can_edit() {
    return $this->test->can_edit();
  }

  /**
   * pagination
   *
   * If there is no next quesiton, just go back to the test
   *
   * @access public
   *
   * @return string prev/next link HTML for navigation through this Question's Test
   */
  public function pagination() {
    $pagination = new View(tu()->get_path("/view/frontend/questions/pagination"), array(
      'prev' => $this->get_prev(),
      'next' => $this->get_next(true)
    ));

    $result = apply_filters('tu_question_pagination', $pagination, $this);

    return $result;
  }

  /**
   * answers
   *
   * Returns the rendered string that is the form for answering this Question.
   * Allow it to be filtered so developers can have custom forms.
   *
   * @access public
   *
   * @return string
   */
  public function answers() {
    $answers = '';
    $answer  = tu()->user->get_answer_to_question($this->ID);
    $type    = $this->type;

    $data = array(
      'question'      => $this,
      'question_type' => $type,
      'users_answer'  => $answer
    );

    if ($type === 'multiple') {
      $data['multiple_answers'] = $this->answers;
    }
    else if ($type === 'single') {
      $data['single_answer'] = $this->correct_answer;
    }

    $view = tu()->get_path('/view/frontend/questions/answers');
    $answers = new View($view, $data).'';

    $answers = apply_filters("tu_render_answers", $answers, $answer, $this);
    $answers = apply_filters("tu_render_answers_{$type}", $answers, $answer, $this);

    return $answers;
  }

  /**
   * _the_content
   *
   * - Fired on `the_content` when this Question is active
   * - Append the form that allows users to answer the Question
   * - Append the pagination to allow navigation through related Questions
   * - Apply some filters to allow developers to customise what is rendered
   *
   * @param string $content
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _the_content($content) {
    $content .= $this->answers() . $this->pagination();

    $content = apply_filters('tu_render_question', $content, $this);
    $content = apply_filters("tu_render_question_{$this->type}", $content, $this);

    return $content;
  }

  /**
   * _add_assets
   *
   * - Fired on `wp_enqueue_scripts` when this Question is active.
   * - Add the script that allows for saving of answers via AJAX (if enabled)
   * - Add the script that allows for arrow key navigation through questions
   * - Always add the script that deals with the test timer
   * - Fire another action specific to Questions, so that develoeprs can enqueue
   *   scripts and styles on the front end when creating custom questions.
   *
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_script('tu_frontend_tests');
    wp_enqueue_script('tu_frontend_questions');

    if (tu()->config['general']['arrow_key_navigation']['questions']) {
      wp_enqueue_script('tu_frontend_kbd_shortcuts');
    }

    do_action('tu_question_frontend_assets');
  }

  /**
   * save
   *
   * Save this Question as normal, then go through all the questions in the Test
   * that this question belongs to, updating them to always be ordered nicely,
   * and set the title too.
   *
   * @param array $data
   *
   * @access public
   */
  public function save($data = array()) {
    parent::save($data);

    $this->auto_title_and_order();
  }

  /**
   * has_custom_title
   *
   * A Question's title is inferred from its post_content.
   *
   * However, if the user has enabled 'title' support on the Question Post Type
   * and they appear to have entered a title other than the default, then assume
   * the post has a custom title.
   *
   * @return boolean
   */
  public function has_custom_title() {
    return (
      post_type_supports($this->post_type, 'title') &&
      $this->post_title != sprintf(__('Question %1$s', 'trainup'), $this->menu_order)
    );
  }

  /**
   * auto_title_and_order
   *
   * - Fired when this Question is saved
   * - Loop through all the questions in this question's Test and set their
   *   titles and menu order.
   *
   * @access private
   */
  private function auto_title_and_order() {
    global $wpdb;

    foreach ($this->test->questions as $i => $question) {
      $i++;

      $data = array('menu_order' => $i);

      if (!$this->has_custom_title()) {
        $data['post_title'] = sprintf(__('Question %1$s', 'trainup'), $i);
        $data['post_name']  = sanitize_title($data['post_title']);
      }

      $wpdb->update($wpdb->posts, $data, array('ID' => $question->ID));
    }
  }

  /**
   * delete
   *
   * - Delete this actual Question post (if a hard delete is being performed).
   * - When a Question is deleted, also clear any temporary answers that
   *   Trainees may have saved against it, just to clean up a little.
   * - Also delete any files that were uploaded in response to this Question.
   * - Just double check that this instance actually has a related WP post,
   *   because we need the post ID to delete files. But if delete() is called
   *   when the post has already been deleted then bad things could happen.
   *
   * @param boolean $hard Delete the actual post
   *
   * @access public
   */
  public function delete($hard = true) {
    if (!$this->ID) return;

    $this->clear_temporary_answers();
    $this->delete_files();

    if ($hard) {
      parent::delete();
    }
  }

  /**
   * clear_temporary_answers
   *
   * Remove attempts from Trainees at answering this Question.
   *
   * @access public
   */
  public function clear_temporary_answers() {
    global $wpdb;

    $wpdb->query("
      DELETE FROM {$wpdb->usermeta}
      WHERE  meta_key = 'tu_answer_{$this->ID}'
    ");
  }

  /**
   * get_featured_image
   *
   * A Question's featured image is taken from the Level that the Test is in
   * that the Question belongs to.
   *
   * @access public
   *
   * @return string The URL of the image
   */
  public function get_featured_image() {
    return $this->test->level->featured_image;
  }

  /**
   * get_breadcrumb_trail
   *
   * Returns the array of breadcrumbs for this Question. Let them be filtered
   * so that developers can customise them.
   *
   * @access public
   *
   * @return array
   */
  public function get_breadcrumb_trail() {
    $crumbs = parent::get_breadcrumb_trail();

    array_shift($crumbs);

    $crumbs = array_merge(
      $this->test->breadcrumb_trail,
      $crumbs
    );

    $result = apply_filters('tu_question_crumbs', $crumbs);

    return $result;
  }

  /**
   * get_next
   *
   * - Loop through the Questions in this Question's Test, and get the one
   *   that comes after this one.
   * - Optionally, if there is no next Question, just go back to the Test
   * - This is a 'hack' because WordPress' get_adjacent_post is naff
   *
   * @param boolean $return_to_test
   *
   * @access public
   *
   * @return object|null The next question
   */
  public function get_next($return_to_test = false) {
    $questions = $this->test->questions;
    $next      = null;

    for ($i = 0, $l = count($questions); $i < $l; $i++) {
      if ($questions[$i]->ID === $this->ID && $i < $l - 1) {
        $next = $questions[$i+1];
        break;
      }
    }

    return $next ?: ($return_to_test ? $this->test : null);
  }

  /**
   * get_prev
   *
   * @see get_next
   * @access public
   *
   * @return object|null The previous question
   */
  public function get_prev() {
    $questions = $this->test->questions;

    for ($i = 0, $l = count($questions); $i < $l; $i++) {
      if ($questions[$i]->ID === $this->ID && $i > 0) {
        return $questions[$i-1];
      }
    }
  }

  /**
   * delete_files
   *
   * Delete all files that have been uploaded to this Question at any point
   * in time, by any user.
   *
   * @access private
   */
  public function delete_files() {
    $dir  = wp_upload_dir();
    $slug = tu()->get_slug();
    $path = "{$dir['basedir']}/{$slug}";

    $user_paths = (array)glob("{$path}/*", GLOB_ONLYDIR);

    foreach ($user_paths as $user_path) {

      $question_path = "{$user_path}/{$this->ID}";

      if (!file_exists($question_path)) continue;

      $attempt_paths = (array)glob("{$question_path}/*", GLOB_ONLYDIR);

      foreach ($attempt_paths as $attempt_path) {

        $file_paths = (array)glob("{$attempt_path}/*");

        foreach ($file_paths as $file_path) {
          unlink($file_path);
        }

        rmdir($attempt_path);
      }
      rmdir($question_path);
    }
  }

  /**
   * get_upload_path
   *
   * Trainees can attach files to a certain Question types.
   * They are stored in a directory in this format:
   * [user_id]/[question_id]/[attempt_number]
   * This is so we can track different versions of the files.
   * Optionally accept a specific attempt number at the Question's Test,
   * defaulting to their latest attempt.
   *
   * @param object $user
   * @param integer|null $resit_number
   *
   * @access public
   */
  public function get_upload_path($user, $resit_number = null) {
    $dir  = wp_upload_dir();
    $slug = tu()->get_slug();
    $num  = is_null($resit_number)
      ? $user->get_resit_attempts($this->test->ID)
      : $resit_number;

    $path = "{$dir['basedir']}/{$slug}/{$user->ID}/{$this->ID}/{$num}";

    return $path;
  }

  /**
   * get_uploads
   *
   * Return files uploaded to this Question by a Trainee.
   * By default, return the uploaded files for the most recent attempt at the
   * Test that the Quesiton is in. Optionally accept a resit number.
   *
   * @param object $user
   * @param integer|null $resit_number
   *
   * @access public
   *
   * @return array
   */
  public function get_uploads($user, $resit_number = null) {
    $files = glob($this->get_upload_path($user, $resit_number).'/*');
    return $files ?: array();
  }


}
