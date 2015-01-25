<?php

/**
 * The dynamic Resource post types
 *
 * @package Train-Up!
 * @subpackage Resources
 */

namespace TU;

class Resource_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for resources.
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_resource';

  /**
   * $level_id
   *
   * The ID of the Level post that this Resource post type is for.
   *
   * @var integer
   *
   * @access public
   */
  public $level_id;

  /**
   * $is_dynamic
   *
   * A flag as to whether or not this post type is dynamic. In the case of
   * Resources, it is true because each Level has its own resource-post-type.
   *
   * @var boolean
   *
   * @access public
   */
  public $is_dynamic = true;

  /**
   * __construct
   * 
   * @param integer $level_id ID of the Level that this Resource post type is for
   *
   * @access public
   */
  public function __construct($level_id) {
    $this->level_id      = $level_id;
    $this->name          = "{$this->slug}_{$level_id}";
    $this->admin_handler = __NAMESPACE__."\\Resource_admin";
  }

  /**
   * refresh
   *
   * Load the level that this Resource post type is for, and refresh the post
   * type. I.e. Re-set the options that are passed to `register_post_type`.
   * Basically prime the post type ready for caching.
   * 
   * @access public
   */
  public function refresh() {
    $level = Levels::factory($this->level_id);

    $this->set_options($level);
  }

  /**
   * set_options
   *
   * Set the options on this Resource post type, so that it can be serialised.
   * These are the settings passed to WordPress' `register_post_type`
   *
   * @param object $level
   * 
   * @access protected
   */
  protected function set_options() {
    $level     = func_get_arg(0);
    $title     = $level->post_title;
    $resources = tu()->config['resources'];

    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
      . '/' .
      sanitize_title_with_dashes($resources['single'])
      . '/' .
      get_page_uri($level->ID)
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
        'name'          => "{$title} {$resources['plural']}",
        'singular_name' => "{$title} {$resources['single']}",
        'add_new_item'  => sprintf(__('Add new %1$s %2$s', 'trainup'), $title, strtolower($resources['single'])),
        'edit_item'     => sprintf(__('Edit', 'trainup')),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'title',
        'editor',
        'page-attributes'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

}


 
