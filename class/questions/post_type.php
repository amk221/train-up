<?php

/**
 * The dynamic Question post type
 *
 * @package Train-Up!
 * @subpackage Questions
 */

namespace TU;

class Question_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for questions.
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_question';

  /**
   * $test_id
   *
   * The ID of the Test post that this Question post type is for.
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
   * Questions, it is true because each Test has its own question-post-type.
   *
   * @var boolean
   *
   * @access public
   */
  public $is_dynamic = true;
  
  /**
   * __construct
   * 
   * @param integer $test_id ID of the Test that this Question post type is for
   *
   * @access public
   */
  public function __construct($test_id) {
    $this->test_id       = $test_id;
    $this->name          = "{$this->slug}_{$test_id}";
    $this->admin_handler = __NAMESPACE__."\\Question_admin";
  }

  /**
   * refresh
   *
   * Load the test that this Question post type is for, and refresh the post
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
   * Set the options on this Question post type, so that it can be serialised.
   * These are the settings passed to WordPress' `register_post_type`
   *
   * @param object $test
   * 
   * @access protected
   */
  public function set_options() {
    $test = func_get_arg(0);
    
    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
      . '/' .
      sanitize_title_with_dashes(__('question', 'trainup'))
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
        'name'          => sprintf(__('%1$s Questions', 'trainup'), $test->post_title),
        'singular_name' => sprintf(__('%1$s Question', 'trainup'), $test->post_title),
        'add_new_item'  => sprintf(__('Add a new Question to %1$s', 'trainup'), $test->post_title),
        'edit_item'     => sprintf(__('Edit Question for %1$s', 'trainup'), $test->post_title),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'editor',
        'page-attributes'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

  /**
   * set_shortcodes
   *
   * Set the shortcodes on this Question post type, so that it can be serialised
   * 
   * @access protected
   */
  protected function set_shortcodes() {
    $this->shortcodes = array(
      'random_number' => array(
        'shortcode'  => __('random_number', 'trainup'),
        'attributes' => array('between' => 'N,M')
      )
    );
  }

  /**
   * shortcode_random_number
   *
   * @param array $attributes
   * 
   * @access protected
   *
   * @return integer A random number between the range sent in the parameters.
   */
  protected function shortcode_random_number($attributes) {
    list($lower, $upper) = explode(',', $attributes['between']);

    if ($lower === 'N') $lower = 0;
    if ($upper === 'M') $upper = 100;

    return rand($lower, $upper);
  }

}


 
