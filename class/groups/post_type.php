<?php

/**
 * The Group post type
 *
 * @package Train-Up!
 * @subpackage Groups
 */

namespace TU;

class Group_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for groups.
   * (Because Groups are not a dynamic post type, this is the same as $name)
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_group';

  /**
   * $name
   *
   * The name of this post type
   *
   * @var string
   *
   * @access public
   */
  public $name = 'tu_group';

  /**
   * set_options
   *
   * - Set the options on this Group post type, so that it can be serialised.
   *   These are the settings passed to WordPress' `register_post_type`
   * - Note: We've set a rewrite URL, however at the moment groups are only
   *   used on the backend.
   * 
   * @access protected
   */
  protected function set_options() {
    $groups = tu()->config['groups'];

    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
      . '/' .
      sanitize_title_with_dashes($groups['single'])
    );

    $this->options = array(
      'hierarchical'        => true,
      'public'              => false,
      'show_ui'             => true,
      'show_in_menu'        => 'tu_plugin',
      'show_in_admin_bar'   => false,
      'exclude_from_search' => true,
      'publicly_queryable'  => false,
      'map_meta_cap'        => true,
      'capability_type'     => $this->slug,
      'has_archive'         => false,
      'labels' => array(
        'name'          => $groups['plural'],
        'singular_name' => $groups['single'],
        'add_new_item'  => sprintf(__('Add a new %1$s', 'trainup'), $groups['single']),
        'edit_item'     => sprintf(__('Edit %1$s', 'trainup'), $groups['single']),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'title'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

}


 
