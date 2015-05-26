<?php

/**
 * The Test post type
 *
 * @package Train-Up!
 * @subpackage Tests
 */

namespace TU;

class Test_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for tests.
   * (Because Tests are not a dynamic post type, this is the same as $name)
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_test';

  /**
   * $name
   *
   * The name of this post type
   *
   * @var string
   *
   * @access public
   */
  public $name = 'tu_test';

  /**
   * set_options
   *
   * Set the options on this Test post type, so that it can be serialised.
   * These are the settings passed to WordPress' `register_post_type`
   *
   * @access protected
   */
  protected function set_options() {
    $tests = tu()->config['tests'];

    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
      . '/' .
      sanitize_title_with_dashes($tests['single'])
    );

    $this->options = array(
      'hierarchical'      => true,
      'public'            => true,
      'show_ui'           => true,
      'show_in_menu'      => 'tu_plugin',
      'show_in_admin_bar' => false,
      'map_meta_cap'      => true,
      'capability_type'   => $this->slug,
      'has_archive'       => false,
      'labels' => array(
        'name'          => $tests['plural'],
        'singular_name' => $tests['single'],
        'add_new_item'  => sprintf(__('Add a new %1$s', 'trainup'), $tests['single']),
        'edit_item'     => sprintf(__('Edit %1$s', 'trainup'), $tests['single']),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'editor'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

  /**
   * set_shortcodes
   *
   * Set the shortcodes on this Test post type, so that it can be serialised.
   *
   * @access protected
   */
  protected function set_shortcodes() {
    $_trainee  = simplify(tu()->config['trainees']['single']);
    $_test     = simplify(tu()->config['tests']['single']);
    $_level    = simplify(tu()->config['levels']['single']);

    $this->shortcodes = array(
      'test_level_title' => array(
        'shortcode'  => sprintf(__('%1$s_%2$s_title', 'trainup'), $_test, $_level),
        'attributes' => array()
      ),
      'number_of_test_questions' => array(
        'shortcode'  => sprintf(__('number_of_%1$s_questions', 'trainup'), $_test),
        'attributes' => array()
      ),
      'trainee_started_test' => array(
        'shortcode'  => sprintf(__('%1$s_started_%2$s', 'trainup'), $_trainee, $_test),
        'attributes' => array()
      ),
      '!trainee_started_test' => array(
        'shortcode'  => sprintf(__('!%1$s_started_%2$s', 'trainup'), $_trainee, $_test),
        'attributes' => array()
      ),
      'trainee_finished_test' => array(
        'shortcode'  => sprintf(__('%1$s_finished_%2$s', 'trainup'), $_trainee, $_test),
        'attributes' => array()
      ),
      '!trainee_finished_test' => array(
        'shortcode'  => sprintf(__('!%1$s_finished_%2$s', 'trainup'), $_trainee, $_test),
        'attributes' => array()
      ),
      'trainee_submitted_answers' => array(
        'shortcode'  => sprintf(__('%1$s_submitted_answers', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      '!trainee_submitted_answers' => array(
        'shortcode'  => sprintf(__('!%1$s_submitted_answers', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      'list_test_questions' => array(
        'shortcode'  => sprintf(__('list_%1$s_questions', 'trainup'), $_test),
        'attributes' => array()
      ),
      'start_test_link' => array(
        'shortcode'  => sprintf(__('start_%1$s_link', 'trainup'), $_test),
        'attributes' => array(
          'text'        => sprintf(__('Start the %1$s', 'trainup'), $_test) . '&nbsp;&raquo;',
          'redirect_to' => ''
        )
      ),
      'finish_test_link' => array(
        'shortcode'  => sprintf(__('finish_%1$s_link', 'trainup'), $_test),
        'attributes' => array(
          'text'        => __('Submit my answers', 'trainup'),
          'redirect_to' => ''
        )
      ),
      'test_time_remaining' => array(
        'shortcode'  => sprintf(__('%1$s_time_remaining', 'trainup'), $_test),
        'attributes' => array(
          'format' => '%h hours, %i minutes and %s seconds'
        )
      ),
      'test_percent_complete' => array(
        'shortcode'  => sprintf(__('%1$s_percent_complete', 'trainup'), $_test),
        'attributes' => array()
      ),
      'test_results_table' => array(
        'shortcode'  => sprintf(__('%1$s_results_table', 'trainup'), $_test),
        'attributes' => array(
          'limit'   => 10,
          'columns' => 'avatar, rank, user_name'
        )
      ),
      'resume_questions_link' => array(
        'shortcode' => sprintf(__('resume_questions_link', 'trainup')),
        'attributes' => array(
          'text' => __('Resume questions', 'trainup')
        )
      )
    );
  }

  /**
   * shortcode_number_of_test_questions
   *
   * @access protected
   *
   * @return integer The number of questions that the active test has
   */
  protected function shortcode_number_of_test_questions() {
    return count(tu()->test->questions);
  }

  /**
   * shortcode_start_test_link
   *
   * - Callback for the 'start_test_link' shortcode
   * - Output a hyperlink that starts the active test for the currently logged
   *   in trainee.
   * - Optionally allow the link text to be set via the `text` attribute
   * - redirect_to attribute was added later, hence the isset check.
   *
   * @param array $attributes
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_start_test_link($attributes) {
    $args = array('tu_action' => 'start');

    if (!empty($attributes['redirect_to'])) {
      $args['tu_redirect'] = $attributes['redirect_to'];
    }

    $href = add_query_arg($args, tu()->test->url);
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-start-test-link'>{$text}</a>";
  }

  /**
   * shortcode_finish_test_link
   *
   * - Callback for the 'finish_test_link' shortcode
   * - Output a hyperlink that finishes the active test for the currently logged
   *   in trainee. This will create a Result post for them, and append the
   *   result data to the user's archive.
   * - Optionally allow the link text to be set via the `text` attribute
   * - redirect_to attribute was added later, hense the isset check.
   *
   * @param array $attributes
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_finish_test_link($attributes) {
    $args = array('tu_action' => 'finish');

    if (!empty($attributes['redirect_to'])) {
      $args['tu_redirect'] = $attributes['redirect_to'];
    }

    $href = add_query_arg($args, tu()->test->url);
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-finish-test-link'>{$text}</a>";
  }

  /**
   * shortcode_test_level_title
   *
   * - Callback for the 'start_test_link' shortcode
   * - Output the title of the Level that this Test belongs to.
   *   (at the moment, all Tests have the same title as their corresponding
   *   Level anyway).
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_test_level_title() {
    return tu()->test->level->post_title;
  }

  /**
   * shortcode_trainee_started_test
   *
   * - Callback for the 'trainee_started_test' shortcode
   * - Output the content wrapped by this shortcode if the active test has
   *   been started by the currently logged in user
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_trainee_started_test($attributes, $content) {
    if (tu()->user->started_test(tu()->test->ID)) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_trainee_started_test
   *
   * - Callback for the '!trainee_started_test' shortcode
   * - Output the content wrapped by this shortcode if the active test has
   *   *not* been started by the currently logged in user
   * - Note: A test is considered started if it has been started or finished
   *   in this context, really what we are saying is has the user 'taken' the
   *   test.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_trainee_started_test($attributes, $content) {
    if (
      !tu()->user->started_test(tu()->test->ID) &&
      !tu()->user->finished_test(tu()->test->ID)
    ) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_trainee_finished_test
   *
   * - Callback for the 'trainee_finished_test' shortcode
   * - Output the content wrapped by this shortcode if the active test has
   *   been completed by the currently logged in user
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_trainee_finished_test($attributes, $content) {
    if (tu()->user->finished_test(tu()->test->ID)) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_trainee_finished_test
   *
   * - Callback for the '!trainee_finished_test' shortcode
   * - Output the content wrapped by this shortcode if the active test has
   *   *not* been completed by the currently logged in user
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_trainee_finished_test($attributes, $content) {
    if (!tu()->user->finished_test(tu()->test->ID)) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_trainee_submitted_answers
   *
   * - Callback for the 'trainee_submitted_answers' shortcode
   * - Output the content wrapped by this shortcode if the currently logged in
   *   trainee has just submitted their answers to the active test.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_trainee_submitted_answers($attributes, $content) {
    if (isset($_GET['tu_action']) && $_GET['tu_action'] === 'finish') {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_trainee_submitted_answers
   *
   * - Callback for the '!trainee_submitted_answers' shortcode
   * - Output the content wrapped by this shortcode if the currently logged in
   *   trainee has not just submitted their answers to the active test.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_trainee_submitted_answers($attributes, $content) {
    if (!(isset($_GET['tu_action']) && $_GET['tu_action'] === 'finish')) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_list_test_questions
   *
   * @access protected
   *
   * @return string An ordered list of the questions that belong to the active
   * Test
   */
  protected function shortcode_list_test_questions() {
    return '<ol class="tu-list tu-list-questions">'.wp_list_pages(array(
      'sort_column' => 'menu_order',
      'sort_order'  => 'ASC',
      'echo'        => false,
      'title_li'    => '',
      'meta_key'    => 'tu_test_id',
      'meta_value'  => tu()->test->ID,
      'post_type'   => 'tu_question_'.tu()->test->ID,
      'walker'      => new Question_walker
    )).'</ol>';
  }

  /**
   * shortcode_test_time_remaining
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_test_time_remaining($attributes) {
    $format    = $attributes['format'];
    $remaining = tu()->test->get_time_remaining(tu()->user);
    $remaining = $remaining->format($format);

    return "
      <span class='tu-time-remaining' data-format='{$format}'>
        {$remaining}
      </span>
    ";
  }

  /**
   * shortcode_test_percent_complete
   *
   * @access protected
   *
   * @return integer
   */
  protected function shortcode_test_percent_complete() {
    return tu()->test->get_percent_complete(tu()->user);
  }

  /**
   * shortcode_test_results_table
   *
   * - Callback for the 'test_results_table' shortcode
   * - Outputs a table of result data for the active test
   * - Order by percentage by default, because that allows us to show the `rank` col.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_test_results_table($attributes, $content) {
    $archives = tu()->test->get_archives(array(
      'limit'    => $attributes['limit'],
      'order_by' => 'percentage',
      'order'    => 'DESC'
    ));

    return new View(tu()->get_path('/view/frontend/results/table'), array(
      'archives' => $archives,
      'columns'  => array_flip(array_map('trim', explode(',', $attributes['columns'])))
    ));
  }

  /**
   * shortcode_resume_questions_link
   *
   * - Callback for the 'resume_questions_link' shortcode
   * - Outputs a hyperlink to the next unanswered question in the test.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_resume_questions_link($attributes, $content) {
    $questions = tu()->test->questions;
    $next      = null;

    foreach ($questions as $question) {
      if (!tu()->user->has_answered_question($question)) {
        $next = $question;
        break;
      }
    }

    $href = $next ? $next->url : ($questions ? $questions[0]->url : '#');
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-resume-questions-link'>{$text}</a>";
  }

}



