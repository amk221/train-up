<?php

/**
 * The dynamic Result post types
 *
 * @package Train-Up!
 * @subpackage Results
 */

namespace TU;

class Result_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for results.
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_result';

  /**
   * $test_id
   *
   * The ID of the Test post that this Result post type is for.
   *
   * @var integer
   *
   * @access public
   */
  public $test_id;

  /**
   * $is_dynamic
   *
   * A flag as to whether or not this post type is dynamic. In the case of
   * Results, it is true because each Test has its own result-post-type.
   *
   * @var boolean
   *
   * @access public
   */
  public $is_dynamic = true;

  /**
   * __construct
   * 
   * @param integer $test_id ID of the Test that this Result post type is for
   *
   * @access public
   */
  public function __construct($test_id) {
    $this->test_id       = $test_id;
    $this->name          = "{$this->slug}_{$test_id}";
    $this->admin_handler = __NAMESPACE__."\\Result_admin";
  }

  /**
   * refresh
   * 
   * Load the test that this Result post type is for, and refresh the post
   * type. I.e. Re-set the options that are passed to `register_post_type` and
   * the shortcodes. Basically prime the post type ready for caching.
   *
   * @access public
   */
  public function refresh() {
    $test = Tests::factory($this->test_id);

    $this->set_options($test);
    $this->set_shortcodes();
  }

  /**
   * set_options
   *
   * Set the options on this Result post type, so that it can be serialised.
   * These are the settings passed to WordPress' `register_post_type`
   *
   * @param object $test
   * 
   * @access protected
   */
  protected function set_options() {
    $test = func_get_arg(0);

    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
      . '/' .
      sanitize_title_with_dashes(__('result', 'trainup'))
      . '/' .
      get_page_uri($test->ID)
    );

    $this->options = array(
      'hierarchical'      => true,
      'public'            => true,
      'show_ui'           => true,
      'show_in_menu'      => false,
      'show_in_admin_bar' => false,
      'map_meta_cap'      => true,
      'capability_type'   => $this->slug,
      'has_archive'       => false,
      'labels' => array(
        'name'          => sprintf(__('%1$s results', 'trainup'), $test->post_title),
        'singular_name' => sprintf(__('%1$s result', 'trainup'), $test->post_title),
        'add_new_item'  => sprintf(__('Add new %1$s result', 'trainup'), $test->post_title),
        'edit_item'     => __('Edit', 'trainup'),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'title',
        'editor',
        'comments'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

  /**
   * set_shortcodes
   *
   * Set the shortcodes on this Result post type, so that it can be serialised
   * 
   * @access protected
   */
  protected function set_shortcodes() {
    $_test    = simplify(tu()->config['tests']['single']);
    $_trainee = simplify(tu()->config['trainees']['single']);

    $this->shortcodes = array(
      'result_test_title' => array(
        'shortcode'  => sprintf(__('result_%1$s_title', 'trainup'), $_test),
        'attributes' => array()
      ),
      'resit_test_link' => array(
        'shortcode'  => sprintf(__('result_%1$s_link', 'trainup'), $_test),
        'attributes' => array('text' => __('resit', 'trainup'))
      ),
      'result_mark' => array(
        'shortcode'  => __('result_mark', 'trainup'),
        'attributes' => array()
      ),
      'result_out_of' => array(
        'shortcode'  => __('result_out_of', 'trainup'),
        'attributes' => array()
      ),
      'result_percentage' => array(
        'shortcode'  => __('result_percentage', 'trainup'),
        'attributes' => array()
      ),
      'result_grade' => array(
        'shortcode'  => __('result_grade', 'trainup'),
        'attributes' => array()
      ),
      'passed_test' => array(
        'shortcode'  => sprintf(__('passed_%1$s', 'trainup'), $_test),
        'attributes' => array()
      ),
      '!passed_test' => array(
        'shortcode'  => sprintf(__('!passed_%1$s', 'trainup'), $_test),
        'attributes' => array()
      ),
      'can_resit_test' => array(
        'shortcode'  => sprintf(__('can_resit_%1$s', 'trainup'), $_test),
        'attributes' => array()
      ),
      '!can_resit_test' => array(
        'shortcode'  => sprintf(__('!can_resit_%1$s', 'trainup'), $_test),
        'attributes' => array()
      ),
      'trainee_first_name' => array(
        'shortcode'  => sprintf(__('%1$s_first_name', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      'trainee_last_name' => array(
        'shortcode'  => sprintf(__('%1$s_last_name', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      'archived_answers' => array(
        'shortcode'  => __('archived_answers', 'trainup'),
        'attributes' => array()
      ),
      'result_rank' => array(
        'shortcode'  => __('result_grade', 'trainup'),
        'attributes' => array()
      ),
      'result_date' => array(
        'shortcode'  => sprintf(__('result_date', 'trainup'), $_test),
        'attributes' => array(
          'format' => 'jS M Y'
        )
      )
    );
  }

  /**
   * shortcode_resit_test_link
   *
   * - Callback for the 'resit_test_link' shortcode
   * - Load the active Result's Test and get its URL, append a resit parameter
   * - Allow customisation of the link text via the 'text' attribute.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_resit_test_link($attributes, $content) {
    $href = add_query_arg(array('tu_action' => 'resit'), tu()->result->test->url);
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-resit-test-link'>{$text}</a>";
  }

  /**
   * get_archived_result_value
   * 
   * - Shortcut function that loads the active Result's User and then gets their
   *   archive for the Test that the Result is for.
   * - Returns the specific key requested, e.g mark, or percentage etc.
   *
   * @param string $key
   *
   * @access private
   *
   * @return string
   */
  private function get_archived_result_value($key) {
    $archive = tu()->result->user->get_archive(tu()->result->test->ID);
    return $archive[$key];
  }

  /**
   * shortcode_result_test_title
   * 
   * @access protected
   *
   * @return string
   */
  protected function shortcode_result_test_title() {
    return tu()->result->test->post_title;
  }

  /**
   * shortcode_result_mark
   * 
   * @access protected
   *
   * @return string
   */
  protected function shortcode_result_mark() {
    return $this->get_archived_result_value('mark');
  }

  /**
   * shortcode_result_out_of
   * 
   * @access protected
   *
   * @return string
   */
  protected function shortcode_result_out_of() {
    return $this->get_archived_result_value('out_of');
  }

  /**
   * shortcode_result_percentage
   * 
   * @access protected
   *
   * @return string
   */
  protected function shortcode_result_percentage() {
    return $this->get_archived_result_value('percentage');
  }

  /**
   * shortcode_result_grade
   * 
   * @access protected
   *
   * @return string
   */
  protected function shortcode_result_grade() {
    return $this->get_archived_result_value('grade');
  }

  /**
   * shortcode_passed_test
   *
   * - Callback for the 'passed_test' shortcode
   * - Output the content wrapped by this shortcode if the active result status
   *   is passed.
   * 
   * @param array $attributes 
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_passed_test($attributes, $content) {
    if ($this->get_archived_result_value('passed')) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_passed_test
   *
   * - Callback for the '!passed_test' shortcode
   * - Output the content wrapped by this shortcode if the active result status
   *   is failed.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_passed_test($attributes, $content) {
    if (!$this->get_archived_result_value('passed')) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_can_resit_test
   *
   * - Callback for the 'can_resit_test' shortcode
   * - Output the content wrapped by this shortcode if the active user is
   *   actually allowed to do a resit.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_can_resit_test($attributes, $content) {
    list($can_resit) = tu()->result->user->can_resit_test(tu()->result->test);

    if ($can_resit) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_can_resit_test
   *
   * - Callback for the '!can_resit_test' shortcode
   * - Output the content wrapped by this shortcode if the active isn't
   *   actually allowed to do a resit.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_can_resit_test($attributes, $content) {
    list($can_resit) = tu()->result->user->can_resit_test(tu()->result->test);

    if (!$can_resit) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_trainee_first_name
   *
   * @access protected
   *
   * @return string The first name of the user who the active result is for.
   */
  protected function shortcode_trainee_first_name() {
    return tu()->result->user->first_name;
  }

  /**
   * shortcode_trainee_last_name
   *
   * @access protected
   *
   * @return string The last name of the user who the active result is for.
   */
  protected function shortcode_trainee_last_name() {
    return tu()->result->user->last_name;
  }

  /**
   * shortcode_archived_answers
   * 
   * @access protected
   *
   * @return string A rendered view of the user's attempted answers.
   */
  protected function shortcode_archived_answers() {
    return tu()->result->user->archived_answers(tu()->result->test->ID);
  }

  /**
   * shortcode_result_rank
   * 
   * @access protected
   *
   * @return integer The Trainee's rank compared to other Trainees for the
   * active test.
   */
  protected function shortcode_result_rank() {
    return tu()->result->user->get_rank(tu()->result->test->ID);
  }

  /**
   * shortcode_result_date
   * 
   * @param mixed $attributes
   * @param mixed $content
   *
   * @access protected
   *
   * @return string The date the Trainee took the Test relating to the active
   * Test Result post.
   */
  protected function shortcode_result_date($attributes, $content) {
    $date = $this->get_archived_result_value('date_submitted');
    return date_i18n($attributes['format'], strtotime($date));
  }

}


 
